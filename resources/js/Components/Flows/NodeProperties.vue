<script setup>
import { ref, computed, watch } from 'vue';
import { X } from 'lucide-vue-next';
import { flowNodeTypes, colorClasses } from '../Blocks/blockTypes.js';
import BlockFormResolver from '../Blocks/BlockFormResolver.vue';
import ErBadge from '@/Components/Ui/ErBadge.vue';

const props = defineProps({
    node: Object,
    canUpdate: Boolean,
    allNodeIds: { type: Array, default: () => [] },
    allStateKeys: { type: Array, default: () => [] },
    declaredStateKeys: { type: Array, default: () => [] },
    possiblyDeclaredStateKeys: { type: Array, default: () => [] },
    botValidationMessages: { type: Object, default: () => ({}) },
    botId: { type: [Number, String], default: null },
});

const emit = defineEmits(['update', 'rename', 'close']);

const nodeType = computed(() => flowNodeTypes.find(t => t.type === props.node?.type));

const localData = ref({ ...props.node?.data });
const localId = ref(props.node?.id ?? '');
const idError = ref('');

const inspectorTab = ref('params'); // params | validation | connections

const validationCount = computed(() => props.node?.data?.validation?.length ?? 0);

watch(() => props.node?.id, (newId) => {
    localData.value = { ...props.node?.data };
    localId.value = newId ?? '';
    idError.value = '';
    inspectorTab.value = 'params';
});

watch(localData, (val) => {
    if (props.node) {
        emit('update', props.node.id, { ...val });
    }
}, { deep: true });

function validateAndRenameId() {
    const newId = localId.value.trim();
    if (!newId) {
        idError.value = 'ID не может быть пустым';
        return;
    }
    if (newId === props.node.id) {
        idError.value = '';
        return;
    }
    if (!/^[a-zA-Z_][a-zA-Z0-9_]*$/.test(newId)) {
        idError.value = 'Только латиница, цифры и _';
        return;
    }
    if (props.allNodeIds.includes(newId)) {
        idError.value = 'Такой ID уже существует';
        return;
    }
    idError.value = '';
    emit('rename', props.node.id, newId);
}

const nodeColorMap = {
    ask_text: '#2a5ca0', ask_keyboard: '#2a5ca0',
    reply_text: '#b03030', reply_keyboard: '#b03030', reply_media: '#b03030',
    save_state: '#3a7a3a', condition: '#c87020',
    api_call: '#3a72c4', on_complete: '#5a6878',
    on_cancel: '#5a6878', start: '#5a6878',
}

function nodeBadgeColor(type) {
    const map = {
        ask_text: 'bl', ask_keyboard: 'bl',
        reply_text: 'rd', reply_keyboard: 'rd', reply_media: 'rd',
        save_state: 'gr', condition: 'or',
        api_call: 'bl',
    }
    return map[type] ?? 'nt'
}
</script>

<template>
    <div class="inspector">
        <!-- Заголовок инспектора -->
        <div class="insp-h">
            <div class="insp-h-main">
                <ErBadge :color="nodeBadgeColor(node.type)">{{ nodeType?.label ?? node.type }}</ErBadge>
                <span class="insp-title">{{ node.id }}</span>
                <button class="insp-close" @click="emit('close')" title="Закрыть">
                    <X :size="14" />
                </button>
            </div>
        </div>

        <!-- Суб-табы -->
        <div class="insp-tabs">
            <button
                class="insp-tab"
                :class="{ act: inspectorTab === 'params' }"
                @click="inspectorTab = 'params'"
            >Параметры</button>
            <button
                class="insp-tab"
                :class="{ act: inspectorTab === 'validation' }"
                @click="inspectorTab = 'validation'"
            >
                Валидация
                <span v-if="validationCount > 0" class="insp-tab-cnt">{{ validationCount }}</span>
            </button>
            <button
                class="insp-tab"
                :class="{ act: inspectorTab === 'connections' }"
                @click="inspectorTab = 'connections'"
            >Связи</button>
        </div>

        <!-- Содержимое табов -->
        <template v-if="inspectorTab === 'params'">
            <div class="insp-body">
                <div class="mb-3">
                    <label class="block text-xs font-medium text-gray-500">ID узла</label>
                    <div v-if="canUpdate" class="mt-0.5">
                        <input v-model="localId" @blur="validateAndRenameId" @keydown.enter="validateAndRenameId"
                            class="w-full rounded border-gray-300 text-xs font-mono placeholder-gray-400"
                            :class="idError ? 'border-red-400' : ''" />
                        <p v-if="idError" class="mt-0.5 text-xs text-red-500">{{ idError }}</p>
                    </div>
                    <p v-else class="mt-0.5 text-xs text-gray-600 font-mono">{{ node.id }}</p>
                </div>

                <div v-if="canUpdate">
                    <BlockFormResolver
                        :type="node.type"
                        v-model="localData"
                        :all-state-keys="allStateKeys"
                        :declared-state-keys="declaredStateKeys"
                        :possibly-declared-state-keys="possiblyDeclaredStateKeys"
                        :bot-validation-messages="botValidationMessages"
                        :bot-id="botId"
                    />
                </div>
                <div v-else class="text-xs text-gray-500">
                    <pre class="whitespace-pre-wrap">{{ JSON.stringify(node.data, null, 2) }}</pre>
                </div>
            </div>
        </template>

        <template v-if="inspectorTab === 'validation'">
            <div class="insp-body">
                <p class="insp-empty">Правила валидации настраиваются в параметрах узла (вкладка ask_text / ask_keyboard).</p>
            </div>
        </template>

        <template v-if="inspectorTab === 'connections'">
            <div class="insp-body">
                <p class="insp-empty">Связи управляются на канвасе</p>
            </div>
        </template>
    </div>
</template>

<style scoped>
.inspector {
    width: 300px;
    background: var(--surface);
    border-left: 1px solid var(--bdr);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    flex-shrink: 0;
}
.insp-h {
    padding: 8px 10px;
    border-bottom: 1px solid var(--bdr);
    background: linear-gradient(180deg, #f4f6f8 0%, #fff 100%);
    flex-shrink: 0;
}
.insp-h-main { display: flex; align-items: center; gap: 7px; margin-bottom: 4px; }
.insp-title { font-size: 12px; font-weight: 600; color: var(--ink); }
.insp-close { margin-left: auto; background: none; border: none; cursor: pointer; color: var(--ink-3); padding: 2px; display: flex; }
.insp-close:hover { color: var(--ink); }
.insp-tabs {
    display: flex;
    border-bottom: 1px solid var(--bdr);
    flex-shrink: 0;
}
.insp-tab {
    padding: 0 12px;
    height: 28px;
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 11px;
    font-weight: 500;
    color: var(--ink-3);
    background: none;
    border: none;
    border-bottom: 2px solid transparent;
    margin-bottom: -1px;
    cursor: pointer;
    font-family: var(--font);
}
.insp-tab:hover { color: var(--ink); }
.insp-tab.act { color: var(--blue-d); border-bottom-color: var(--blue); font-weight: 600; }
.insp-tab-cnt { font-size: 10px; background: var(--blue-soft); color: var(--blue-d); border-radius: 8px; padding: 0 4px; font-family: var(--mono); }
.insp-body { flex: 1; overflow-y: auto; padding: 10px 12px; }
.insp-empty { font-size: 12px; color: var(--ink-3); text-align: center; padding: 20px; }
</style>
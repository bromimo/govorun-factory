<script setup>
import { ref, computed, watch } from 'vue';
import {
    X,
    MessageCircleQuestion,
    MessageSquare,
    Save,
    GitBranch,
    Globe,
    CheckCircle2,
    XCircle,
    Play,
    Plug,
} from 'lucide-vue-next';
import { flowNodeTypes, NODE_TYPE_COLORS } from '../Blocks/blockTypes.js';
import BlockFormResolver from '../Blocks/BlockFormResolver.vue';

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

const nodeColors = computed(() => NODE_TYPE_COLORS[props.node?.type] ?? NODE_TYPE_COLORS.on_complete);
const nodeGradFrom = computed(() => nodeColors.value.gradFrom);
const nodeGradTo = computed(() => nodeColors.value.gradTo);
const nodeFill = computed(() => nodeColors.value.fill);
const nodeStripe = computed(() => nodeColors.value.stripe);

const iconMap = {
    ask:         MessageCircleQuestion,
    reply:       MessageSquare,
    save_state:  Save,
    condition:   GitBranch,
    api_call:    Globe,
    on_complete: CheckCircle2,
    on_cancel:   XCircle,
    start:       Play,
};
const nodeIcon = computed(() => iconMap[props.node?.type] ?? Plug);

const localData = ref({ ...props.node?.data });
const localId = ref(props.node?.id ?? '');
const idError = ref('');

watch(() => props.node?.id, (newId) => {
    localData.value = { ...props.node?.data };
    localId.value = newId ?? '';
    idError.value = '';
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
</script>

<template>
    <div class="inspector">
        <div class="insp-h">
            <div class="insp-icon">
                <component :is="nodeIcon" :size="18" color="white" />
            </div>
            <div class="insp-meta">
                <div class="insp-type">{{ nodeType?.label ?? node.type }}</div>
                <div class="insp-id">{{ node.id }}</div>
            </div>
            <button class="insp-close" @click="emit('close')" title="Закрыть">
                <X :size="14" />
            </button>
        </div>

        <div class="insp-body">
            <div class="mb-3">
                <label class="field-lbl">ID узла</label>
                <div v-if="canUpdate" class="mt-1">
                    <input v-model="localId" @blur="validateAndRenameId" @keydown.enter="validateAndRenameId"
                        class="field-input-id"
                        :class="idError ? 'field-input-id--err' : ''" />
                    <p v-if="idError" class="field-lbl-err">{{ idError }}</p>
                </div>
                <p v-else class="field-id-ro">{{ node.id }}</p>
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
            <div v-else class="field-id-ro">
                <pre style="white-space: pre-wrap;">{{ JSON.stringify(node.data, null, 2) }}</pre>
            </div>
        </div>
    </div>
</template>

<style scoped>
.inspector {
    flex: 1;
    min-width: 0;
    background: var(--surface);
    border-left: 1px solid var(--bdr);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
.insp-h {
    display: flex;
    align-items: stretch;
    border-bottom: 1px solid var(--bdr);
    flex-shrink: 0;
}
.insp-icon {
    width: 56px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(160deg, v-bind(nodeGradFrom) 0%, v-bind(nodeGradTo) 100%);
}
.insp-meta {
    flex: 1;
    min-width: 0;
    padding: 10px 10px;
    background: v-bind(nodeFill);
}
.insp-type {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: v-bind(nodeStripe);
    line-height: 1.2;
}
.insp-id {
    font-size: 12px;
    font-family: var(--mono, monospace);
    color: var(--ink-2);
    margin-top: 2px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.insp-close {
    flex-shrink: 0;
    width: 36px;
    background: v-bind(nodeFill);
    border: none;
    border-left: 1px solid var(--bdr);
    cursor: pointer;
    color: var(--ink-3);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: color .1s;
}
.insp-close:hover { color: var(--red, #b03030); }
.insp-body {
    flex: 1;
    overflow-y: auto;
    padding: 12px 12px;
}
.field-input-id {
    width: 100%;
    height: 26px;
    padding: 0 8px;
    border: 1px solid var(--bdr-d);
    border-radius: var(--r-sm);
    background: #fff;
    color: var(--ink);
    font-size: 12px;
    font-family: var(--mono, monospace);
    box-sizing: border-box;
    margin-top: 4px;
}
.field-input-id:focus { outline: none; border-color: var(--blue); box-shadow: 0 0 0 2px rgba(58,114,196,.2); }
.field-input-id--err { border-color: var(--red); }
.field-lbl-err { font-size: 11px; color: var(--red); margin-top: 2px; }
.field-id-ro { font-size: 12px; font-family: var(--mono, monospace); color: var(--ink-2); margin-top: 2px; }
</style>

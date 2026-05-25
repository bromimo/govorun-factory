<script setup>
import { ref, computed, watch } from 'vue';
import ErInput from '@/Components/Ui/ErInput.vue';
import ErSelect from '@/Components/Ui/ErSelect.vue';
import ErButton from '@/Components/Ui/ErButton.vue';

const props = defineProps({
    button: { type: Object, required: true },
    keyboardType: { type: String, default: 'inline' },
    anchor: { type: Object, default: null },
});

const emit = defineEmits(['save', 'cancel', 'open-full']);

const local = ref({ ...props.button });

watch(() => props.button, (b) => {
    local.value = { ...b };
}, { deep: true });

const showTypeSelect = computed(() => props.keyboardType === 'inline');
const showActionField = computed(() => local.value.type === 'action');
const showUrlField = computed(() => local.value.type === 'url');
const showParamField = computed(() => local.value.type === 'action');

const isValid = computed(() => {
    if (!local.value.label?.trim()) return false;
    if (props.keyboardType === 'inline') {
        if (local.value.type === 'action' && !local.value.action?.trim()) return false;
        if (local.value.type === 'url' && !local.value.url?.trim()) return false;
    }
    return true;
});

function save() {
    if (!isValid.value) return;
    emit('save', { ...local.value });
}

const style = computed(() => props.anchor ? ({
    top: `${props.anchor.y}px`,
    left: `${props.anchor.x}px`,
}) : {});
</script>

<template>
    <Teleport to="body">
        <div class="kbd-quick-bd" @click.self="$emit('cancel')">
            <div class="kbd-quick" :style="style" @click.stop>
                <div class="kbd-quick-h">
                    <span>Редактировать кнопку</span>
                    <button type="button" class="kbd-quick-x" @click="$emit('cancel')" title="Закрыть">×</button>
                </div>

                <div class="kbd-quick-body">
                    <label class="field-lbl">Текст</label>
                    <ErInput v-model="local.label" long />

                    <template v-if="showTypeSelect">
                        <label class="field-lbl">Тип</label>
                        <ErSelect v-model="local.type" long>
                            <option value="action">Действие</option>
                            <option value="url">URL</option>
                            <option value="contact">Контакт</option>
                            <option value="location">Локация</option>
                        </ErSelect>
                    </template>

                    <template v-if="showActionField">
                        <label class="field-lbl">Action key</label>
                        <ErInput v-model="local.action" placeholder="например, confirm" long />
                    </template>

                    <template v-if="showUrlField">
                        <label class="field-lbl">URL</label>
                        <ErInput v-model="local.url" type="url" placeholder="https://…" long />
                    </template>

                    <template v-if="showParamField">
                        <label class="field-lbl">Параметры (JSON, опционально)</label>
                        <ErInput v-model="local.param" placeholder='{"id": 1}' long />
                    </template>
                </div>

                <div class="kbd-quick-foot">
                    <ErButton size="sm" type="button" @click="$emit('open-full')">Подробнее…</ErButton>
                    <span style="flex: 1"></span>
                    <ErButton size="sm" type="button" @click="$emit('cancel')">Отмена</ErButton>
                    <ErButton size="sm" variant="primary" type="button" :disabled="!isValid" @click="save">
                        Применить
                    </ErButton>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<style>
.kbd-quick-bd {
    position: fixed;
    inset: 0;
    background: rgba(26,34,48,.18);
    z-index: 9000;
}
.kbd-quick {
    position: absolute;
    background: var(--surface);
    border: 1px solid var(--bdr);
    border-radius: var(--r-md);
    box-shadow: var(--sh-md);
    min-width: 280px;
    max-width: 360px;
    transform: translate(-50%, 8px);
}
.kbd-quick-h {
    padding: 8px 10px;
    background: linear-gradient(180deg, #f4f6f8 0%, #e8ecf0 100%);
    border-bottom: 1px solid var(--bdr);
    font-size: 12px;
    font-weight: 600;
    color: var(--ink);
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.kbd-quick-x {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 14px;
    color: var(--ink-3);
    padding: 0 4px;
}
.kbd-quick-body {
    padding: 8px 12px;
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.kbd-quick-foot {
    padding: 6px 10px;
    border-top: 1px solid var(--bdr);
    background: var(--surface-2);
    display: flex;
    gap: 6px;
    align-items: center;
}
</style>
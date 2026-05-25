<script setup>
import { ref, watch, computed } from 'vue';
import draggable from 'vuedraggable';
import EmojiPickerPopover from './EmojiPickerPopover.vue';
import VariablePickerPopover from './VariablePickerPopover.vue';
import { insertAtCursor } from '@/utils/insertAtCursor';
import ConfirmModal from '@/Components/Ui/ConfirmModal.vue';

const props = defineProps({
    modelValue: { type: Array, default: () => [] },
    open: { type: Boolean, default: false },
    declaredKeys: { type: Array, default: () => [] },
    keyboardType: { type: String, default: 'inline' },
});

const emit = defineEmits(['update:modelValue', 'update:open']);

const localRows = ref([]);
const paramErrors = ref({});
const confirmRowIdx = ref(null);
const labelInputs = ref({});
const emojiOpen = ref(null);
const varsOpen = ref(null);
const emojiAnchorEl = ref(null);
const varsAnchorEl = ref(null);

function buildInitial() {
    const src = Array.isArray(props.modelValue) ? props.modelValue : [];
    const copy = JSON.parse(JSON.stringify(src));
    if (copy.length === 0) copy.push([]);
    return copy;
}

watch(() => props.open, (isOpen) => {
    if (isOpen) {
        localRows.value = buildInitial();
        paramErrors.value = {};
    }
});

function addRow() {
    localRows.value.push([]);
}

function removeRow(ri) {
    const row = localRows.value[ri];
    if (row.length > 0) {
        confirmRowIdx.value = ri;
        return;
    }
    doRemoveRow(ri);
}

function doRemoveRow(ri = confirmRowIdx.value) {
    localRows.value.splice(ri, 1);
    paramErrors.value = {};
    confirmRowIdx.value = null;
}

function addButton(ri) {
    if (props.keyboardType === 'reply') {
        localRows.value[ri].push({ type: 'text', label: '' });
    } else {
        localRows.value[ri].push({ type: 'action', label: '', action: '' });
    }
}

function removeButton(ri, bi) {
    localRows.value[ri].splice(bi, 1);
    delete paramErrors.value[`${ri}:${bi}`];
}

function onTypeChange(btn) {
    const text = btn.label ?? '';
    const type = btn.type;
    for (const k of Object.keys(btn)) {
        if (k !== 'label' && k !== 'type') delete btn[k];
    }
    btn.label = text;
    btn.type = type;
    if (props.keyboardType === 'inline') {
        if (type === 'action') btn.action = '';
        if (type === 'url') btn.url = '';
    }
}

function paramString(btn) {
    return btn.param ? JSON.stringify(btn.param, null, 2) : '';
}

function onParamInput(ri, bi, btn, ev) {
    const raw = ev.target.value.trim();
    if (raw === '') {
        delete btn.param;
        paramErrors.value[`${ri}:${bi}`] = false;
        return;
    }
    try {
        const parsed = JSON.parse(raw);
        if (typeof parsed !== 'object' || Array.isArray(parsed) || parsed === null) {
            paramErrors.value[`${ri}:${bi}`] = true;
            return;
        }
        btn.param = parsed;
        paramErrors.value[`${ri}:${bi}`] = false;
    } catch (e) {
        paramErrors.value[`${ri}:${bi}`] = true;
    }
}

function urlInvalid(btn) {
    if (btn.type !== 'url') return false;
    const u = btn.url ?? '';
    return !u.trim() || !/^https?:\/\//.test(u);
}

function actionInvalid(btn) {
    if (btn.type !== 'action') return false;
    const a = btn.action?.trim() ?? '';
    return a.length > 0 && !/^[a-zA-Z0-9_-]+$/.test(a);
}

const duplicateActions = computed(() => {
    const counts = {};
    for (const row of localRows.value) {
        for (const btn of row) {
            if (btn.type === 'action') {
                const a = btn.action?.trim() ?? '';
                if (a) counts[a] = (counts[a] ?? 0) + 1;
            }
        }
    }
    return new Set(Object.keys(counts).filter(a => counts[a] >= 2));
});

const isValid = computed(() => {
    for (const isErr of Object.values(paramErrors.value)) {
        if (isErr) return false;
    }
    for (const row of localRows.value) {
        for (const btn of row) {
            if (!btn.label?.trim()) return false;
            if (props.keyboardType === 'inline') {
                if (btn.type === 'action') {
                    if (!btn.action?.trim()) return false;
                    if (actionInvalid(btn)) return false;
                    if (duplicateActions.value.has(btn.action.trim())) return false;
                }
                if (urlInvalid(btn)) return false;
            }
        }
    }
    return true;
});

function save() {
    if (!isValid.value) return;
    const cleaned = localRows.value.filter(row => row.length > 0);
    emit('update:modelValue', cleaned);
    emit('update:open', false);
}

function cancel() {
    emit('update:open', false);
}

function setLabelRef(ri, bi, el) {
    const key = `${ri}:${bi}`;
    if (el) {
        labelInputs.value[key] = el;
    } else {
        delete labelInputs.value[key];
    }
}

function toggleEmoji(ri, bi, event) {
    const key = `${ri}:${bi}`;
    if (emojiOpen.value === key) {
        emojiOpen.value = null;
        return;
    }
    varsOpen.value = null;
    emojiAnchorEl.value = event.currentTarget;
    emojiOpen.value = key;
}

function toggleVars(ri, bi, event) {
    const key = `${ri}:${bi}`;
    if (varsOpen.value === key) {
        varsOpen.value = null;
        return;
    }
    emojiOpen.value = null;
    varsAnchorEl.value = event.currentTarget;
    varsOpen.value = key;
}

function onEmoji(emoji) {
    const key = emojiOpen.value;
    if (!key) return;
    insertAtCursor(labelInputs.value[key] ?? null, emoji);
    emojiOpen.value = null;
}

function onVar(varKey) {
    const key = varsOpen.value;
    if (!key) return;
    insertAtCursor(labelInputs.value[key] ?? null, `{{${varKey}}}`);
    varsOpen.value = null;
}
</script>

<template>
    <teleport to="body">
        <div v-if="open" class="kem-overlay" @click.self="cancel">
            <div class="kem-dialog">
                <div class="kem-head">
                    <span class="kem-title">Клавиатура</span>
                    <button type="button" @click="cancel" class="kem-x">×</button>
                </div>

                <div class="kem-body">
                    <div v-for="(row, ri) in localRows" :key="ri" class="kem-row">
                        <div class="kem-row-head">
                            <span class="kem-row-lbl">Ряд {{ ri + 1 }}</span>
                            <button type="button" @click="removeRow(ri)" class="er-btn sm dng">× ряд</button>
                        </div>

                        <draggable v-model="localRows[ri]" :group="{ name: 'keyboard-buttons' }"
                            :animation="150" handle=".drag-handle"
                            item-key="id"
                            class="kem-btns-wrap">
                            <template #item="{ element: btn, index: bi }">
                                <div class="kem-btn-card">
                                    <div class="kem-btn-top">
                                        <span class="drag-handle kem-drag">⋮⋮</span>
                                        <input v-model="btn.label" placeholder="Текст"
                                            :ref="(el) => setLabelRef(ri, bi, el)"
                                            :class="['field-input kem-label-inp', !btn.label?.trim() ? 'field-input--error' : '']" />
                                        <button type="button" @click="toggleEmoji(ri, bi, $event)"
                                            title="Вставить эмодзи" class="kem-icon-btn">😀</button>
                                        <button type="button" @click="toggleVars(ri, bi, $event)"
                                            title="Вставить переменную" class="kem-icon-btn kem-icon-btn--mono">{…}</button>
                                        <button type="button" @click="removeButton(ri, bi)" class="field-del kem-rm">×</button>
                                    </div>
                                    <select v-if="keyboardType === 'inline'"
                                        v-model="btn.type" @change="onTypeChange(btn)" class="field-sel">
                                        <option value="action">action</option>
                                        <option value="url">url</option>
                                        <option value="contact">contact</option>
                                        <option value="location">location</option>
                                    </select>
                                    <select v-if="keyboardType === 'reply'"
                                        v-model="btn.type" @change="onTypeChange(btn)" class="field-sel">
                                        <option value="text">Текст</option>
                                        <option value="contact">Контакт</option>
                                        <option value="location">Геолокация</option>
                                    </select>
                                    <template v-if="keyboardType === 'inline'">
                                        <input v-if="btn.type === 'action'" v-model="btn.action" placeholder="action"
                                            :class="['field-input mono', !btn.action?.trim() || actionInvalid(btn) || duplicateActions.has(btn.action?.trim()) ? 'field-input--error' : '']" />
                                        <textarea v-if="btn.type === 'action'"
                                            :value="paramString(btn)" @input="onParamInput(ri, bi, btn, $event)"
                                            placeholder="param (JSON, опционально)"
                                            :class="['field-ta mono', paramErrors[`${ri}:${bi}`] ? 'field-input--error' : '']"
                                            rows="2" />
                                        <input v-if="btn.type === 'url'" v-model="btn.url" placeholder="https://..."
                                            :class="['field-input', urlInvalid(btn) ? 'field-input--error' : '']" />
                                    </template>
                                </div>
                            </template>
                        </draggable>

                        <button type="button" @click="addButton(ri)" class="er-btn sm">+ кнопка</button>
                    </div>

                    <button type="button" @click="addRow" class="er-btn sm kem-add-row">+ новый ряд</button>
                </div>

                <div class="kem-foot">
                    <button type="button" @click="cancel" class="er-btn">Отменить</button>
                    <button type="button" @click="save" :disabled="!isValid" class="er-btn pr">✓ Сохранить</button>
                </div>
            </div>
        </div>
    </teleport>
    <EmojiPickerPopover v-if="emojiOpen"
        :anchor="emojiAnchorEl"
        @select="onEmoji"
        @close="emojiOpen = null" />
    <VariablePickerPopover v-if="varsOpen"
        :anchor="varsAnchorEl"
        :declared-keys="declaredKeys"
        @select="onVar"
        @close="varsOpen = null" />

<ConfirmModal
    :show="confirmRowIdx !== null"
    title="Удалить ряд?"
    :message="confirmRowIdx !== null ? `В ряду ${localRows[confirmRowIdx]?.length} кнопок. Удалить безвозвратно?` : ''"
    @confirm="doRemoveRow()"
    @cancel="confirmRowIdx = null"
/>
</template>

<style scoped>
.kem-overlay {
    position: fixed; inset: 0; z-index: 50;
    display: flex; align-items: center; justify-content: center;
    background: rgba(0,0,0,.5); padding: 16px;
}
.kem-dialog {
    width: 100%; max-width: 760px; max-height: 90vh;
    overflow-y: auto; border-radius: 4px;
    background: var(--surface); box-shadow: var(--sh-md);
    display: flex; flex-direction: column;
}
.kem-head {
    display: flex; align-items: center; justify-content: space-between;
    padding: 10px 16px; border-bottom: 1px solid var(--bdr); flex-shrink: 0;
}
.kem-title { font-size: 13px; font-weight: 600; color: var(--ink); }
.kem-x {
    width: 24px; height: 24px; display: flex; align-items: center; justify-content: center;
    background: none; border: none; cursor: pointer; font-size: 18px;
    color: var(--ink-4); border-radius: var(--r-sm);
}
.kem-x:hover { background: var(--surface-2); color: var(--ink); }
.kem-body { padding: 12px; display: flex; flex-direction: column; gap: 10px; flex: 1; overflow-y: auto; }
.kem-foot { display: flex; justify-content: flex-end; gap: 6px; padding: 10px 16px; border-top: 1px solid var(--bdr); flex-shrink: 0; }

.kem-row {
    border: 1px solid var(--bdr); border-radius: var(--r-sm);
    padding: 10px; display: flex; flex-direction: column; gap: 8px;
    background: var(--surface-2);
}
.kem-row-head { display: flex; align-items: center; justify-content: space-between; }
.kem-row-lbl { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; color: var(--ink-4); }
.kem-btns-wrap { display: flex; flex-wrap: wrap; gap: 8px; min-height: 40px; }
.kem-btn-card {
    border: 1px solid var(--bdr-d); border-radius: var(--r-sm);
    background: var(--surface); padding: 8px;
    display: flex; flex-direction: column; gap: 5px; width: 220px;
}
.kem-btn-top { display: flex; align-items: center; gap: 4px; }
.kem-drag { cursor: grab; color: var(--ink-4); font-size: 13px; user-select: none; flex-shrink: 0; }
.kem-label-inp { text-align: center; }
.kem-icon-btn {
    flex-shrink: 0; width: 22px; height: 22px; display: flex; align-items: center; justify-content: center;
    background: none; border: none; cursor: pointer; font-size: 13px; color: var(--ink-4); border-radius: var(--r-sm);
}
.kem-icon-btn:hover { background: var(--surface-2); color: var(--ink); }
.kem-icon-btn--mono { font-family: var(--mono); font-size: 11px; }
.kem-rm { font-size: 14px; line-height: 1; }
.kem-add-row { width: 100%; justify-content: center; }
</style>

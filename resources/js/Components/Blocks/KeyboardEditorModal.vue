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
        <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            @click.self="cancel">
            <div class="w-full max-w-3xl max-h-[90vh] overflow-y-auto rounded-lg bg-white shadow-xl">
                <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3">
                    <h2 class="text-lg font-semibold">Клавиатура</h2>
                    <button type="button" @click="cancel" class="text-gray-400 hover:text-gray-600 text-xl">×</button>
                </div>

                <div class="p-4 space-y-4">
                    <div v-for="(row, ri) in localRows" :key="ri"
                        class="rounded border border-gray-300 p-3 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-medium text-gray-500">Ряд {{ ri + 1 }}</span>
                            <button type="button" @click="removeRow(ri)"
                                class="text-red-400 hover:text-red-600 text-xs">× ряд</button>
                        </div>

                        <draggable v-model="localRows[ri]" :group="{ name: 'keyboard-buttons' }"
                            :animation="150" handle=".drag-handle"
                            item-key="id"
                            class="flex flex-wrap gap-2 min-h-[40px]">
                            <template #item="{ element: btn, index: bi }">
                                <div class="rounded border border-gray-300 bg-gray-50 p-2 space-y-1.5 w-64">
                                    <div class="flex items-center gap-1">
                                        <span class="drag-handle cursor-move text-gray-400 select-none">⋮⋮</span>
                                        <input v-model="btn.label" placeholder="Текст"
                                            :ref="(el) => setLabelRef(ri, bi, el)"
                                            :class="['flex-1 rounded border text-sm px-2 py-1 text-center', !btn.label?.trim() ? 'border-red-400' : 'border-gray-300']" />
                                        <button type="button"
                                            @click="toggleEmoji(ri, bi, $event)"
                                            title="Вставить эмодзи"
                                            class="inline-flex h-6 w-6 shrink-0 items-center justify-center text-base text-gray-400 hover:text-gray-600">
                                            😀
                                        </button>
                                        <button type="button"
                                            @click="toggleVars(ri, bi, $event)"
                                            title="Вставить переменную"
                                            class="inline-flex h-6 w-6 shrink-0 items-center justify-center font-mono text-sm text-gray-400 hover:text-gray-600">
                                            {…}
                                        </button>
                                        <button type="button" @click="removeButton(ri, bi)"
                                            class="text-red-400 hover:text-red-600">×</button>
                                    </div>
                                    <select v-if="keyboardType === 'inline'"
                                        v-model="btn.type" @change="onTypeChange(btn)"
                                        class="w-full rounded border-gray-300 text-xs py-1">
                                        <option value="action">action</option>
                                        <option value="url">url</option>
                                        <option value="contact">contact</option>
                                        <option value="location">location</option>
                                    </select>

                                    <select v-if="keyboardType === 'reply'"
                                        v-model="btn.type" @change="onTypeChange(btn)"
                                        class="w-full rounded border-gray-300 text-xs py-1">
                                        <option value="text">Текст</option>
                                        <option value="contact">Контакт</option>
                                        <option value="location">Геолокация</option>
                                    </select>

                                    <template v-if="keyboardType === 'inline'">
                                        <input v-if="btn.type === 'action'" v-model="btn.action" placeholder="action"
                                            :class="['w-full rounded border text-xs px-2 py-1', !btn.action?.trim() || actionInvalid(btn) || duplicateActions.has(btn.action?.trim()) ? 'border-red-400' : 'border-gray-300']" />

                                        <textarea v-if="btn.type === 'action'"
                                            :value="paramString(btn)" @input="onParamInput(ri, bi, btn, $event)"
                                            placeholder="param (JSON, опционально)"
                                            :class="['w-full rounded border text-xs px-2 py-1 font-mono', paramErrors[`${ri}:${bi}`] ? 'border-red-400' : 'border-gray-300']"
                                            rows="2" />

                                        <input v-if="btn.type === 'url'" v-model="btn.url" placeholder="https://..."
                                            :class="['w-full rounded border text-xs px-2 py-1', urlInvalid(btn) ? 'border-red-400' : 'border-gray-300']" />
                                    </template>
                                </div>
                            </template>
                        </draggable>

                        <button type="button" @click="addButton(ri)"
                            class="text-xs text-indigo-600 hover:text-indigo-800">+ кнопка</button>
                    </div>

                    <button type="button" @click="addRow"
                        class="w-full rounded border-2 border-dashed border-gray-300 py-2 text-sm text-gray-500 hover:border-indigo-400 hover:text-indigo-600">
                        + новый ряд
                    </button>
                </div>

                <div class="flex justify-end gap-2 border-t border-gray-200 px-4 py-3">
                    <button type="button" @click="cancel"
                        class="rounded-md border border-gray-300 px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-50">
                        Отменить
                    </button>
                    <button type="button" @click="save" :disabled="!isValid"
                        class="rounded-md bg-indigo-600 px-3 py-1.5 text-sm text-white hover:bg-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed">
                        ✓ Сохранить
                    </button>
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

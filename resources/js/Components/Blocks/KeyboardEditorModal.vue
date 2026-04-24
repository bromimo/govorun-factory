<script setup>
import { ref, watch, computed } from 'vue';
import draggable from 'vuedraggable';

const props = defineProps({
    modelValue: { type: Array, default: () => [] },
    open: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue', 'update:open']);

const localRows = ref([]);
const paramErrors = ref({}); // key "r:b" → boolean (invalid JSON)

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
    if (row.length > 0 && !confirm(`Удалить ряд из ${row.length} кнопк(и)?`)) return;
    localRows.value.splice(ri, 1);
    // Индексы сдвинулись — проще очистить карту ошибок (она восстановится по инпуту).
    paramErrors.value = {};
}

function addButton(ri) {
    localRows.value[ri].push({ type: 'action', label: '', action: '' });
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
    if (type === 'action') btn.action = '';
    if (type === 'url') btn.url = '';
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

const isValid = computed(() => {
    for (const isErr of Object.values(paramErrors.value)) {
        if (isErr) return false;
    }
    for (const row of localRows.value) {
        for (const btn of row) {
            if (!btn.label?.trim()) return false;
            if (btn.type === 'action' && !btn.action?.trim()) return false;
            if (urlInvalid(btn)) return false;
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
                                            :class="['flex-1 rounded border text-sm px-2 py-1', !btn.label?.trim() ? 'border-red-400' : 'border-gray-300']" />
                                        <button type="button" @click="removeButton(ri, bi)"
                                            class="text-red-400 hover:text-red-600">×</button>
                                    </div>
                                    <select v-model="btn.type" @change="onTypeChange(btn)"
                                        class="w-full rounded border-gray-300 text-xs py-1">
                                        <option value="action">action</option>
                                        <option value="url">url</option>
                                        <option value="contact">contact</option>
                                        <option value="location">location</option>
                                    </select>

                                    <input v-if="btn.type === 'action'" v-model="btn.action" placeholder="action"
                                        :class="['w-full rounded border text-xs px-2 py-1', !btn.action?.trim() ? 'border-red-400' : 'border-gray-300']" />

                                    <textarea v-if="btn.type === 'action'"
                                        :value="paramString(btn)" @input="onParamInput(ri, bi, btn, $event)"
                                        placeholder="param (JSON, опционально)"
                                        :class="['w-full rounded border text-xs px-2 py-1 font-mono', paramErrors[`${ri}:${bi}`] ? 'border-red-400' : 'border-gray-300']"
                                        rows="2" />

                                    <input v-if="btn.type === 'url'" v-model="btn.url" placeholder="https://..."
                                        :class="['w-full rounded border text-xs px-2 py-1', urlInvalid(btn) ? 'border-red-400' : 'border-gray-300']" />
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
</template>

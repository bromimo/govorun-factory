<script setup>
import { ref, onMounted, watch } from 'vue';
import VarsHint from './VarsHint.vue';
import StateWarning from './StateWarning.vue';
import InsertToolbar from './InsertToolbar.vue';
import KeyboardPreview from './KeyboardPreview.vue';
import KeyboardEditorModal from './KeyboardEditorModal.vue';
import { useStateWarnings } from './useStateWarnings.js';

const model = defineModel({ type: Object, default: () => ({ text: '', buttons: [] }) });
const props = defineProps({
    allStateKeys: { type: Array, default: () => [] },
    declaredStateKeys: { type: Array, default: () => [] },
    possiblyDeclaredStateKeys: { type: Array, default: () => [] },
});

const textareaRef = ref(null);
const editorOpen = ref(false);

const { uninitializedKeys, partiallyInitializedKeys, undeclaredKeys } = useStateWarnings(
    () => model.value.text,
    () => props.allStateKeys,
    () => props.declaredStateKeys,
    () => props.possiblyDeclaredStateKeys,
);

function normalizeButtons(value) {
    if (!Array.isArray(value)) return [];
    if (value.length === 0) return [];
    const first = value[0];
    if (!first || typeof first !== 'object') return [];
    if (Array.isArray(first)) return value; // уже новый формат Row[]
    // Legacy flat: каждая кнопка → свой ряд
    return value.map(btn => [{
        type: 'action',
        label: btn.label || '',
        action: btn.action || '',
    }]);
}

onMounted(() => {
    const normalized = normalizeButtons(model.value.buttons);
    if (JSON.stringify(normalized) !== JSON.stringify(model.value.buttons)) {
        model.value = { ...model.value, buttons: normalized };
    }
});

watch(() => model.value.buttons, (val) => {
    const normalized = normalizeButtons(val);
    if (JSON.stringify(normalized) !== JSON.stringify(val)) {
        model.value = { ...model.value, buttons: normalized };
    }
});
</script>

<template>
    <div class="space-y-3">
        <div>
            <div class="flex items-center justify-between">
                <label class="block text-xs font-medium text-gray-500">Текст сообщения</label>
                <InsertToolbar :target="textareaRef" :declared-keys="declaredStateKeys" />
            </div>
            <textarea ref="textareaRef" v-model="model.text" rows="2"
                class="mt-1 w-full rounded border-gray-300 text-sm placeholder-gray-400" />
            <StateWarning :uninitialized-keys="uninitializedKeys" :partially-initialized-keys="partiallyInitializedKeys"
                :undeclared-keys="undeclaredKeys" />
            <VarsHint />
        </div>

        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Клавиатура</label>
            <KeyboardPreview :model-value="model.buttons" class="mb-2" />
            <button type="button" @click="editorOpen = true"
                class="rounded-md border border-gray-300 px-3 py-1.5 text-xs hover:bg-gray-50">
                {{ (model.buttons?.length ?? 0) > 0 ? 'Редактировать клавиатуру' : 'Добавить клавиатуру' }}
            </button>
        </div>

        <KeyboardEditorModal v-model="model.buttons" v-model:open="editorOpen" />
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import KeyboardPreview from './KeyboardPreview.vue';
import KeyboardEditorModal from './KeyboardEditorModal.vue';

const model = defineModel({ type: Object, default: null });
const props = defineProps({
    required: { type: Boolean, default: false },
    declaredKeys: { type: Array, default: () => [] },
});

const editorOpen = ref(false);

const buttons = computed({
    get: () => model.value?.buttons ?? [],
    set: (val) => {
        model.value = { type: 'inline', buttons: val };
    },
});

const hasButtons = computed(() => {
    const b = model.value?.buttons;
    return Array.isArray(b) && b.some(row => Array.isArray(row) && row.length > 0);
});

function clear() {
    model.value = null;
}
</script>

<template>
    <div class="space-y-2">
        <div class="flex items-center justify-between">
            <label class="block text-xs font-medium text-gray-500">Клавиатура</label>
            <button v-if="!required && model" type="button" @click="clear"
                class="text-xs text-red-400 hover:text-red-600">
                Удалить
            </button>
        </div>

        <KeyboardPreview :model-value="buttons" />

        <button type="button" @click="editorOpen = true"
            class="rounded-md border border-gray-300 px-3 py-1.5 text-xs hover:bg-gray-50">
            {{ hasButtons ? 'Редактировать клавиатуру' : 'Добавить кнопки' }}
        </button>

        <KeyboardEditorModal v-model="buttons" v-model:open="editorOpen" :declared-keys="props.declaredKeys" />
    </div>
</template>
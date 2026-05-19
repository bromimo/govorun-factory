<script setup>
import { ref, computed } from 'vue';
import KeyboardPreview from './KeyboardPreview.vue';
import KeyboardEditorModal from './KeyboardEditorModal.vue';
import ConfirmModal from '@/Components/Ui/ConfirmModal.vue';

const model = defineModel({ type: Object, default: null });
const props = defineProps({
    required: { type: Boolean, default: false },
    declaredKeys: { type: Array, default: () => [] },
});

const editorOpen = ref(false);
const pendingType = ref(null);

const keyboardType = computed(() => model.value?.type ?? 'inline');

const buttons = computed({
    get: () => model.value?.buttons ?? [],
    set: (val) => {
        model.value = { ...(model.value ?? { type: 'inline' }), buttons: val };
    },
});

const resize = computed({
    get: () => model.value?.resize ?? false,
    set: (val) => { model.value = { ...(model.value ?? { type: 'reply', resize: false, oneTime: false, buttons: [] }), resize: val }; },
});

const oneTime = computed({
    get: () => model.value?.oneTime ?? false,
    set: (val) => { model.value = { ...(model.value ?? { type: 'reply', resize: false, oneTime: false, buttons: [] }), oneTime: val }; },
});

const hasButtons = computed(() => {
    const b = model.value?.buttons;
    return Array.isArray(b) && b.some(row => Array.isArray(row) && row.length > 0);
});

function switchType(newType) {
    if (newType === keyboardType.value) return;
    if (hasButtons.value) {
        pendingType.value = newType;
        return;
    }
    applyType(newType);
}

function applyType(newType = pendingType.value) {
    pendingType.value = null;
    if (newType === 'reply') {
        model.value = { type: 'reply', resize: false, oneTime: false, buttons: [] };
    } else {
        model.value = { type: 'inline', buttons: [] };
    }
}

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

        <div class="inline-flex rounded-md border border-gray-300 bg-white p-0.5 text-xs">
            <button type="button" @click="switchType('inline')"
                :class="['px-3 py-1 rounded', keyboardType === 'inline' ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-50']">
                Inline
            </button>
            <button type="button" @click="switchType('reply')"
                :class="['px-3 py-1 rounded', keyboardType === 'reply' ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-50']">
                Reply
            </button>
        </div>

        <div v-if="keyboardType === 'reply'" class="flex gap-4 text-xs text-gray-600">
            <label class="flex items-center gap-1">
                <input type="checkbox" v-model="resize" class="rounded" />
                Подогнать размер
            </label>
            <label class="flex items-center gap-1">
                <input type="checkbox" v-model="oneTime" class="rounded" />
                Скрыть после нажатия
            </label>
        </div>

        <KeyboardPreview :model-value="buttons" :keyboard-type="keyboardType" />

        <button type="button" @click="editorOpen = true"
            class="rounded-md border border-gray-300 px-3 py-1.5 text-xs hover:bg-gray-50">
            {{ hasButtons ? 'Редактировать клавиатуру' : 'Добавить кнопки' }}
        </button>

        <KeyboardEditorModal v-model="buttons" v-model:open="editorOpen"
            :declared-keys="props.declaredKeys" :keyboard-type="keyboardType" />

        <ConfirmModal
            :show="!!pendingType"
            title="Сменить тип клавиатуры?"
            message="Все настроенные кнопки будут удалены."
            confirm-label="Продолжить"
            variant="primary"
            @confirm="applyType()"
            @cancel="pendingType = null"
        />
    </div>
</template>

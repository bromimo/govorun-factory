<script setup>
import { ref, onMounted, watch } from 'vue';
import VarsHint from './VarsHint.vue';
import StateWarning from './StateWarning.vue';
import InsertToolbar from './InsertToolbar.vue';
import KeyboardPreview from './KeyboardPreview.vue';
import ValidationEditor from './ValidationEditor.vue';
import KeyboardEditorModal from './KeyboardEditorModal.vue';
import { useStateWarnings } from './useStateWarnings.js';
import { toCamelCase, sanitizeIdentifier, identifierWarning } from '@/utils/translit';

const model = defineModel({ type: Object, default: () => ({ text: '', buttons: [], validation: [] }) });
const props = defineProps({
    allStateKeys: { type: Array, default: () => [] },
    declaredStateKeys: { type: Array, default: () => [] },
    possiblyDeclaredStateKeys: { type: Array, default: () => [] },
    botValidationMessages: { type: Object, default: () => ({}) },
});

const { uninitializedKeys, partiallyInitializedKeys, undeclaredKeys } = useStateWarnings(
    () => model.value.text,
    () => props.allStateKeys,
    () => props.declaredStateKeys,
    () => props.possiblyDeclaredStateKeys,
);

const textareaRef = ref(null);
const editorOpen = ref(false);

const stepNameWarning = ref('');
let stepNameWarningTimer = null;

function autoStepName() {
    const body = toCamelCase(model.value.text ?? '');
    if (!body) return '';
    return 'ask' + body.charAt(0).toUpperCase() + body.slice(1);
}

function onStepNameInput(e) {
    const raw = e.target.value;
    const clean = sanitizeIdentifier(raw);
    const warning = identifierWarning(raw);

    if (warning) {
        stepNameWarning.value = warning;
        clearTimeout(stepNameWarningTimer);
        stepNameWarningTimer = setTimeout(() => { stepNameWarning.value = ''; }, 3000);
    } else {
        stepNameWarning.value = '';
    }

    model.value.stepName = clean;
}

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
                <label class="block text-xs font-medium text-gray-500">Текст вопроса</label>
                <InsertToolbar :target="textareaRef" :declared-keys="declaredStateKeys" />
            </div>
            <textarea ref="textareaRef" v-model="model.text" rows="2"
                class="mt-1 w-full rounded border-gray-300 text-sm placeholder-gray-400" />
            <StateWarning :uninitialized-keys="uninitializedKeys" :partially-initialized-keys="partiallyInitializedKeys"
                :undeclared-keys="undeclaredKeys" />
            <VarsHint />
        </div>

        <div>
            <label class="block text-xs font-medium text-gray-500">Картинка (URL)</label>
            <input v-model="model.image" type="url"
                class="mt-1 w-full rounded border-gray-300 text-sm placeholder-gray-400"
                placeholder="https://example.com/image.jpg" />
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

        <div>
            <label class="block text-xs font-medium text-gray-500">Имя шага</label>
            <div class="mt-1 flex gap-2">
                <input :value="model.stepName ?? ''" type="text"
                    @input="onStepNameInput($event)"
                    class="w-full rounded border-gray-300 text-sm font-mono placeholder-gray-400"
                    :placeholder="autoStepName() || 'askGender'" />
                <button v-if="model.stepName" type="button" @click="model.stepName = ''"
                    class="shrink-0 text-gray-400 hover:text-red-500">
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
            <p class="mt-1 text-xs text-gray-400">
                camelCase. Например: <span class="font-mono">askGender</span>
            </p>
            <p v-if="stepNameWarning" class="mt-1 text-xs text-amber-600">{{ stepNameWarning }}</p>
        </div>

        <ValidationEditor v-model="model.validation" :bot-validation-messages="botValidationMessages" :allowed-rules="['required']" />
    </div>
</template>

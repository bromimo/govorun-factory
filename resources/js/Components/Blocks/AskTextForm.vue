<script setup>
import { ref } from 'vue';
import VarsHint from './VarsHint.vue';
import StateWarning from './StateWarning.vue';
import ValidationEditor from './ValidationEditor.vue';
import InsertToolbar from './InsertToolbar.vue';
import { useStateWarnings } from './useStateWarnings.js';
import { toCamelCase, sanitizeIdentifier, identifierWarning } from '@/utils/translit';

const model = defineModel({ type: Object, default: () => ({ text: '', validation: [] }) });
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
</script>

<template>
    <div>
        <div class="flex items-center justify-between">
            <label class="block text-xs font-medium text-gray-500">Текст вопроса</label>
            <InsertToolbar :target="textareaRef" :declared-keys="declaredStateKeys" />
        </div>
        <textarea ref="textareaRef" v-model="model.text" rows="3" class="mt-1 w-full rounded border-gray-300 text-sm placeholder-gray-400" placeholder="Как вас зовут?" />
        <StateWarning :uninitialized-keys="uninitializedKeys" :partially-initialized-keys="partiallyInitializedKeys" :undeclared-keys="undeclaredKeys" />
        <VarsHint />
        <div class="mt-3">
            <label class="block text-xs font-medium text-gray-500">Картинка (URL)</label>
            <input v-model="model.image" type="url" class="mt-1 w-full rounded border-gray-300 text-sm placeholder-gray-400" placeholder="https://example.com/image.jpg" />
        </div>
        <div class="mt-3">
            <label class="block text-xs font-medium text-gray-500">Имя шага</label>
            <div class="mt-1 flex gap-2">
                <input :value="model.stepName ?? ''" type="text"
                    @input="onStepNameInput($event)"
                    class="w-full rounded border-gray-300 text-sm font-mono placeholder-gray-400"
                    :placeholder="autoStepName() || 'askName'" />
                <button v-if="model.stepName" type="button" @click="model.stepName = ''"
                    class="shrink-0 text-gray-400 hover:text-red-500">
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
            <p class="mt-1 text-xs text-gray-400">
                camelCase. Например: <span class="font-mono">askName</span>
            </p>
            <p v-if="stepNameWarning" class="mt-1 text-xs text-amber-600">{{ stepNameWarning }}</p>
        </div>
        <ValidationEditor v-model="model.validation" :bot-validation-messages="botValidationMessages" />
    </div>
</template>

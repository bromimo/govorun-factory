<script setup>
import VarsHint from './VarsHint.vue';
import StateWarning from './StateWarning.vue';
import ButtonEditor from './ButtonEditor.vue';
import ValidationEditor from './ValidationEditor.vue';
import { useStateWarnings } from './useStateWarnings.js';

const model = defineModel({ type: Object, default: () => ({ text: '', buttons: [], validation: [] }) });
const props = defineProps({
    allStateKeys: { type: Array, default: () => [] },
    declaredStateKeys: { type: Array, default: () => [] },
    botValidationMessages: { type: Object, default: () => ({}) },
});

const { uninitializedKeys, undeclaredKeys } = useStateWarnings(
    () => model.value.text, () => props.allStateKeys, () => props.declaredStateKeys,
);
</script>

<template>
    <div class="space-y-3">
        <div>
            <label class="block text-xs font-medium text-gray-500">Текст вопроса</label>
            <textarea v-model="model.text" rows="2" class="mt-1 w-full rounded border-gray-300 text-sm placeholder-gray-400" />
            <StateWarning :uninitialized-keys="uninitializedKeys" :undeclared-keys="undeclaredKeys" />
            <VarsHint />
        </div>
        <ButtonEditor v-model="model.buttons" />
        <ValidationEditor v-model="model.validation" :bot-validation-messages="botValidationMessages" />
    </div>
</template>

<script setup>
import VarsHint from './VarsHint.vue';
import StateWarning from './StateWarning.vue';
import { useStateWarnings } from './useStateWarnings.js';

const model = defineModel({ type: Object, default: () => ({ text: '' }) });
const props = defineProps({
    allStateKeys: { type: Array, default: () => [] },
    declaredStateKeys: { type: Array, default: () => [] },
});

const { uninitializedKeys, undeclaredKeys } = useStateWarnings(
    () => model.value.text, () => props.allStateKeys, () => props.declaredStateKeys,
);
</script>

<template>
    <div>
        <label class="block text-xs font-medium text-gray-500">Текст вопроса</label>
        <textarea v-model="model.text" rows="3" class="mt-1 w-full rounded border-gray-300 text-sm placeholder-gray-400" placeholder="Как вас зовут?" />
        <StateWarning :uninitialized-keys="uninitializedKeys" :undeclared-keys="undeclaredKeys" />
        <VarsHint />
    </div>
</template>

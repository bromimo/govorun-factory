<script setup>
import VarsHint from './VarsHint.vue';
import StateWarning from './StateWarning.vue';
import { useStateWarnings } from './useStateWarnings.js';

const model = defineModel({ type: Object, default: () => ({ text: '' }) });
const props = defineProps({
    allStateKeys: { type: Array, default: () => [] },
    declaredStateKeys: { type: Array, default: () => [] },
    possiblyDeclaredStateKeys: { type: Array, default: () => [] },
});

const { uninitializedKeys, partiallyInitializedKeys, undeclaredKeys } = useStateWarnings(
    () => model.value.text,
    () => props.allStateKeys,
    () => props.declaredStateKeys,
    () => props.possiblyDeclaredStateKeys,
);
</script>

<template>
    <div>
        <label class="block text-xs font-medium text-gray-500">Текст ответа</label>
        <textarea v-model="model.text" rows="3" class="mt-1 w-full rounded border-gray-300 text-sm placeholder-gray-400" placeholder="Привет, {{user.firstName}}!" />
        <StateWarning :uninitialized-keys="uninitializedKeys" :partially-initialized-keys="partiallyInitializedKeys" :undeclared-keys="undeclaredKeys" />
        <VarsHint />
    </div>
</template>

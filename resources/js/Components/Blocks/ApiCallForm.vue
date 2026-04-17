<script setup>
import VarsHint from './VarsHint.vue';
import StateWarning from './StateWarning.vue';
import { useStateWarnings } from './useStateWarnings.js';

const model = defineModel({ type: Object, default: () => ({ url: '', method: 'GET' }) });
const props = defineProps({
    allStateKeys: { type: Array, default: () => [] },
    declaredStateKeys: { type: Array, default: () => [] },
    possiblyDeclaredStateKeys: { type: Array, default: () => [] },
});

const { uninitializedKeys, partiallyInitializedKeys, undeclaredKeys } = useStateWarnings(
    () => model.value.url,
    () => props.allStateKeys,
    () => props.declaredStateKeys,
    () => props.possiblyDeclaredStateKeys,
);
</script>

<template>
    <div class="space-y-3">
        <div>
            <label class="block text-xs font-medium text-gray-500">URL</label>
            <input v-model="model.url" type="text" class="mt-1 w-full rounded border-gray-300 text-sm placeholder-gray-400" placeholder="https://api.example.com/data" />
            <StateWarning :uninitialized-keys="uninitializedKeys" :partially-initialized-keys="partiallyInitializedKeys" :undeclared-keys="undeclaredKeys" />
            <VarsHint />
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500">Метод</label>
            <select v-model="model.method" class="mt-1 w-full rounded border-gray-300 text-sm placeholder-gray-400">
                <option>GET</option>
                <option>POST</option>
                <option>PUT</option>
                <option>DELETE</option>
            </select>
        </div>
    </div>
</template>

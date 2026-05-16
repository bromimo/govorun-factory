<script setup>
const model = defineModel({ type: Object, default: () => ({}) });
const props = defineProps({ keys: { type: Array, default: () => [] } });

function update(key, value) {
    model.value = { ...model.value, [key]: value };
}
</script>

<template>
    <details class="border rounded p-2" v-if="keys.length">
        <summary class="cursor-pointer text-xs text-gray-600">Подставить состояние ({{ keys.length }})</summary>
        <div v-for="k in keys" :key="k" class="flex items-center gap-2 mt-2">
            <span class="font-mono text-xs text-gray-500 w-32 truncate">{{ k }}</span>
            <input :value="model[k] ?? ''" @input="update(k, $event.target.value)"
                class="flex-1 rounded border-gray-300 text-sm" />
        </div>
    </details>
</template>
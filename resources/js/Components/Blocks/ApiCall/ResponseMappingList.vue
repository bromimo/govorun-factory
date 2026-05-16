<script setup>
const model = defineModel({ type: Array, default: () => [] });

function rename(i, value) {
    const next = [...model.value];
    next[i] = { ...next[i], state_key: value };
    model.value = next;
}
function remove(i) {
    model.value = model.value.filter((_, idx) => idx !== i);
}
</script>

<template>
    <div v-if="model.length" class="border rounded p-2 mt-2">
        <div class="text-xs font-semibold uppercase text-gray-500 mb-2">Сохранённые поля</div>
        <div v-for="(m, i) in model" :key="i" class="flex items-center gap-2 text-xs mb-1">
            <span class="font-mono text-gray-500 flex-1 truncate">{{ m.json_path }}</span>
            <span class="text-gray-400">→ state.</span>
            <input :value="m.state_key" @input="rename(i, $event.target.value)"
                class="rounded border-gray-300 text-xs w-32" />
            <button @click="remove(i)" class="text-red-500 px-1">✕</button>
        </div>
    </div>
</template>
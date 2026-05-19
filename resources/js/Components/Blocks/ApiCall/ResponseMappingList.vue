<script setup>
const model = defineModel({ type: Array, default: () => [] });

function update(i, field, value) {
    const next = [...model.value];
    next[i] = { ...next[i], [field]: value };
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
            <input :value="m.json_path" @input="update(i, 'json_path', $event.target.value)"
                class="rounded border-gray-300 text-xs font-mono flex-1 min-w-0" />
            <span class="text-gray-400 shrink-0">→ state.</span>
            <input :value="m.state_key" @input="update(i, 'state_key', $event.target.value)"
                class="rounded border-gray-300 text-xs w-32 shrink-0" />
            <button type="button" @click="remove(i)" class="text-red-500 px-1 shrink-0">✕</button>
        </div>
    </div>
</template>
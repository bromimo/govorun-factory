<script setup>
const model = defineModel({ type: Array, default: () => [] });

function addButton() {
    model.value = [...model.value, { label: '', action: '' }];
}

function removeButton(index) {
    model.value = model.value.filter((_, i) => i !== index);
}

function updateButton(index, field, value) {
    const updated = [...model.value];
    updated[index] = { ...updated[index], [field]: value };
    model.value = updated;
}
</script>

<template>
    <div class="space-y-2">
        <label class="block text-xs font-medium text-gray-500">Кнопки</label>
        <div v-for="(btn, i) in model" :key="i" class="rounded border border-gray-200 p-2 space-y-1.5">
            <div class="flex items-center justify-between">
                <span class="text-xs text-gray-400">Кнопка {{ i + 1 }}</span>
                <button type="button" @click="removeButton(i)" class="text-red-400 hover:text-red-600 text-sm">x</button>
            </div>
            <input :value="btn.label" @input="updateButton(i, 'label', $event.target.value)"
                placeholder="Текст" class="w-full rounded border-gray-300 text-sm placeholder-gray-400" />
            <input :value="btn.action" @input="updateButton(i, 'action', $event.target.value)"
                placeholder="Action" class="w-full rounded border-gray-300 text-sm placeholder-gray-400" />
        </div>
        <button type="button" @click="addButton" class="text-xs text-indigo-600 hover:text-indigo-800">
            + Добавить кнопку
        </button>
    </div>
</template>

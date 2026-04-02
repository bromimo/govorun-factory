<script setup>
const model = defineModel({ type: Object, default: () => ({ text: '' }) });
</script>

<template>
    <div>
        <label class="block text-xs font-medium text-gray-500">Текст вопроса</label>
        <textarea v-model="model.text" rows="3" class="mt-1 w-full rounded border-gray-300 text-sm placeholder-gray-400" placeholder="Как вас зовут?" />
    </div>
</template>

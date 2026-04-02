<script setup>
const model = defineModel({ type: Object, default: () => ({ field: '' }) });
</script>

<template>
    <div>
        <label class="block text-xs font-medium text-gray-500">Поле для проверки</label>
        <input v-model="model.field" type="text" class="mt-1 w-full rounded border-gray-300 text-sm placeholder-gray-400" placeholder="message.action" />
        <p class="mt-1 text-xs text-gray-400">Значения задаются на исходящих связях (edge labels)</p>
    </div>
</template>

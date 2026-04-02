<script setup>
const model = defineModel({ type: Object, default: () => ({ key: '', source: '' }) });
</script>

<template>
    <div class="space-y-3">
        <div>
            <label class="block text-xs font-medium text-gray-500">Ключ (key)</label>
            <input v-model="model.key" type="text" class="mt-1 w-full rounded border-gray-300 text-sm" placeholder="name" />
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500">Источник (source)</label>
            <input v-model="model.source" type="text" class="mt-1 w-full rounded border-gray-300 text-sm" placeholder="message.text" />
        </div>
    </div>
</template>

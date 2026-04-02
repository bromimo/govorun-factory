<script setup>
import ButtonEditor from './ButtonEditor.vue';

const model = defineModel({ type: Object, default: () => ({ text: '', buttons: [] }) });
</script>

<template>
    <div class="space-y-3">
        <div>
            <label class="block text-xs font-medium text-gray-500">Текст вопроса</label>
            <textarea v-model="model.text" rows="2" class="mt-1 w-full rounded border-gray-300 text-sm" />
        </div>
        <ButtonEditor v-model="model.buttons" />
    </div>
</template>

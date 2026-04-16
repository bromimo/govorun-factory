<script setup>
import BaseNode from './BaseNode.vue';
defineOptions({ inheritAttrs: false });
defineProps(['id', 'data', 'selected']);
</script>
<template>
    <BaseNode :id="id" :selected="selected" label="Вопрос с клавиатурой" color="blue"
        :has-validation="data.validation?.length > 0">
        <img v-if="data.image" :src="data.image" class="mb-1 max-h-24 w-full rounded object-cover" />
        <p v-if="data.text" class="line-clamp-3">{{ data.text }}</p>
        <p v-if="data.buttons?.length" class="mt-0.5 text-gray-400">{{ data.buttons.length }} кнопок</p>
    </BaseNode>
</template>

<script setup>
import BaseNode from './BaseNode.vue';
defineOptions({ inheritAttrs: false });
defineProps(['id', 'data', 'selected']);
</script>
<template>
    <BaseNode :id="id" :selected="selected" label="Вопрос с клавиатурой" color="blue">
        <p v-if="data.text" class="truncate">{{ data.text }}</p>
        <p v-if="data.buttons?.length" class="mt-1 text-gray-400">{{ data.buttons.length }} кнопок</p>
    </BaseNode>
</template>

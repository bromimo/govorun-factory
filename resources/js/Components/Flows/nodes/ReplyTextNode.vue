<script setup>
import BaseNode from './BaseNode.vue';
defineOptions({ inheritAttrs: false });
defineProps(['id', 'data', 'selected']);
</script>
<template>
    <BaseNode :id="id" :selected="selected" label="Ответ текстом" color="pink">
        <p v-if="data.text" class="truncate">{{ data.text }}</p>
    </BaseNode>
</template>

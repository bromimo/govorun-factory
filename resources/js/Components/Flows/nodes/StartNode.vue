<script setup>
import BaseNode from './BaseNode.vue';
defineOptions({ inheritAttrs: false });
defineProps(['id', 'data', 'selected']);
</script>
<template>
    <BaseNode :id="id" :selected="selected" label="Начало" color="gray" :has-input="false">
        <p class="text-gray-400">Старт диалога</p>
    </BaseNode>
</template>

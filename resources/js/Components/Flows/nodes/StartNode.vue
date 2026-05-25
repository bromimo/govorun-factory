<script setup>
import BaseNode from './BaseNode.vue';
defineOptions({ inheritAttrs: false });
defineProps(['id', 'type', 'data', 'selected']);
</script>
<template>
    <BaseNode :id="id" :type="type" :selected="selected" label="Начало" :has-input="false">
        <p class="text-gray-400">Старт диалога</p>
    </BaseNode>
</template>

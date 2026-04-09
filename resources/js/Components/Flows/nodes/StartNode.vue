<script setup>
import BaseNode from './BaseNode.vue';
defineOptions({ inheritAttrs: false });
defineProps(['id', 'data', 'selected']);
</script>
<template>
    <BaseNode :id="id" :selected="selected" label="Начало" color="green" :has-input="false">
        <p class="text-green-600 text-center">Старт диалога</p>
    </BaseNode>
</template>

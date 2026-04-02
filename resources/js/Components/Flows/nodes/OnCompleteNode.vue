<script setup>
import BaseNode from './BaseNode.vue';
defineOptions({ inheritAttrs: false });
defineProps(['id', 'data', 'selected']);
</script>
<template>
    <BaseNode :id="id" :selected="selected" label="Завершение" color="gray" :has-output="false">
        <p class="text-gray-400">Конец диалога</p>
    </BaseNode>
</template>

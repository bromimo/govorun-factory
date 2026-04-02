<script setup>
import BaseNode from './BaseNode.vue';
defineOptions({ inheritAttrs: false });
defineProps(['id', 'data', 'selected']);
</script>
<template>
    <BaseNode :id="id" :selected="selected" label="Отмена" color="gray" :has-output="false">
        <p class="text-gray-400">Диалог отменён</p>
    </BaseNode>
</template>

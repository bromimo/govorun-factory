<script setup>
import BaseNode from './BaseNode.vue';
defineOptions({ inheritAttrs: false });
defineProps(['id', 'type', 'data', 'selected']);
</script>
<template>
    <BaseNode :id="id" :type="type" :selected="selected" label="Отмена" :has-output="false">
        <p class="text-gray-400">Диалог отменён</p>
    </BaseNode>
</template>

<script setup>
import { Handle, Position } from '@vue-flow/core';
import BaseNode from './BaseNode.vue';
defineOptions({ inheritAttrs: false });
defineProps(['id', 'data', 'selected']);
</script>
<template>
    <BaseNode :id="id" :selected="selected" label="API-запрос" color="purple">
        <p v-if="data.url" class="truncate font-mono">{{ data.method }} {{ data.url }}</p>
        <Handle v-if="data.on_error === 'branch'" type="source" :position="Position.Bottom" id="on_error" class="!bg-red-500" />
    </BaseNode>
</template>

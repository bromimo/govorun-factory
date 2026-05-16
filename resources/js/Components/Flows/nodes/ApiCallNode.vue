<script setup>
import { Handle, Position } from '@vue-flow/core';
import BaseNode from './BaseNode.vue';
defineOptions({ inheritAttrs: false });
defineProps(['id', 'data', 'selected']);
</script>
<template>
    <BaseNode :id="id" :selected="selected" label="API-запрос" color="purple" :has-output="data.on_error !== 'branch'">
        <p v-if="data.url" class="truncate font-mono">{{ data.method }} {{ data.url }}</p>
        <template v-if="data.on_error === 'branch'" #output-handles>
            <Handle type="source" :position="Position.Bottom" id="default" style="left: 33%" />
            <Handle type="source" :position="Position.Bottom" id="on_error" style="left: 67%" class="!bg-red-500" />
        </template>
    </BaseNode>
</template>

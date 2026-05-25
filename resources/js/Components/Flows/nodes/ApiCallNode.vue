<script setup>
import { Handle, Position } from '@vue-flow/core';
import BaseNode from './BaseNode.vue';
defineOptions({ inheritAttrs: false });
defineProps(['id', 'type', 'data', 'selected']);
</script>
<template>
    <BaseNode :id="id" :type="type" :selected="selected" label="API-запрос" :has-output="false">
        <p v-if="data.url" class="truncate font-mono">{{ data.method }} {{ data.url }}</p>
        <template #output-handles>
            <template v-if="data.on_error === 'branch'">
                <Handle type="source" :position="Position.Bottom" id="default" style="left: 33%" />
                <Handle type="source" :position="Position.Bottom" id="on_error" style="left: 67%" class="!bg-red-500" />
            </template>
            <Handle v-else type="source" :position="Position.Bottom" id="default" />
        </template>
    </BaseNode>
</template>

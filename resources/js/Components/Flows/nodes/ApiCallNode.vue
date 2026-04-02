<script setup>
import BaseNode from './BaseNode.vue';
defineOptions({ inheritAttrs: false });
defineProps(['id', 'data', 'selected']);
</script>
<template>
    <BaseNode :id="id" :selected="selected" label="API-запрос" color="purple">
        <p v-if="data.url" class="truncate font-mono">{{ data.method }} {{ data.url }}</p>
    </BaseNode>
</template>

<script setup>
import BaseNode from './BaseNode.vue';
defineOptions({ inheritAttrs: false });
defineProps(['id', 'type', 'data', 'selected']);
</script>
<template>
    <BaseNode :id="id" :type="type" :selected="selected" label="Условие">
        <p v-if="data.field" class="truncate font-mono">{{ data.field }}</p>
    </BaseNode>
</template>

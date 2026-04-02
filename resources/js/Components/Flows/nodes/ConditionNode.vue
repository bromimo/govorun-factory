<script setup>
import BaseNode from './BaseNode.vue';
defineOptions({ inheritAttrs: false });
defineProps(['id', 'data', 'selected']);
</script>
<template>
    <BaseNode :id="id" :selected="selected" label="Условие" color="orange">
        <p v-if="data.field" class="truncate font-mono">{{ data.field }}</p>
    </BaseNode>
</template>

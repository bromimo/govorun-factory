<script setup>
import BaseNode from './BaseNode.vue';
defineOptions({ inheritAttrs: false });
defineProps(['id', 'data', 'selected']);
</script>
<template>
    <BaseNode :id="id" :selected="selected" label="Сохранить состояние" color="green">
        <p v-if="data.key" class="truncate font-mono">{{ data.key }} = {{ data.source }}</p>
    </BaseNode>
</template>

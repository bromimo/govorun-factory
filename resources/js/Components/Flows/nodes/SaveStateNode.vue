<script setup>
import { computed } from 'vue';
import BaseNode from './BaseNode.vue';
defineOptions({ inheritAttrs: false });
const props = defineProps(['id', 'type', 'data', 'selected']);

const variables = computed(() => {
    if (Array.isArray(props.data.variables)) return props.data.variables.filter(v => v.key);
    if (props.data.key) return [{ key: props.data.key, source: props.data.source }];
    return [];
});
</script>
<template>
    <BaseNode :id="id" :type="type" :selected="selected" label="Сохранить состояние">
        <div v-if="variables.length" class="space-y-0.5">
            <p v-for="v in variables" :key="v.key" class="truncate font-mono">{{ v.key }} = {{ v.source }}</p>
        </div>
    </BaseNode>
</template>

<script setup>
import { computed } from 'vue';
import BaseNode from './BaseNode.vue';
import { highlightVars } from './highlightVars.js';
defineOptions({ inheritAttrs: false });
const props = defineProps(['id', 'data', 'selected']);
const html = computed(() => highlightVars(props.data.text));
</script>
<template>
    <BaseNode :id="id" :selected="selected" label="Ответ с клавиатурой" color="pink">
        <p v-if="data.text" class="line-clamp-3" v-html="html"></p>
        <p v-if="data.buttons?.length" class="mt-1 text-gray-400">{{ data.buttons.length }} кнопок</p>
    </BaseNode>
</template>

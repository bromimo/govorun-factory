<script setup>
import { computed } from 'vue';
import BaseNode from './BaseNode.vue';
import { highlightVars } from './highlightVars.js';
defineOptions({ inheritAttrs: false });
const props = defineProps(['id', 'data', 'selected']);

const typeLabels = { photo: 'Фото', video: 'Видео', audio: 'Аудио', document: 'Документ', animation: 'GIF' };
const captionHtml = computed(() => highlightVars(props.data.caption));
</script>

<template>
    <BaseNode :id="id" :selected="selected" label="Ответ медиа" color="pink">
        <p v-if="data.media_type" class="truncate">{{ typeLabels[data.media_type] || data.media_type }}</p>
        <p v-if="data.caption" class="line-clamp-3 text-gray-500" v-html="captionHtml"></p>
    </BaseNode>
</template>

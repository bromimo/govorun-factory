<script setup>
import BaseNode from './BaseNode.vue';
defineOptions({ inheritAttrs: false });
defineProps(['id', 'data', 'selected']);

const typeLabels = { photo: 'Фото', video: 'Видео', audio: 'Аудио', document: 'Документ', animation: 'GIF' };
</script>

<template>
    <BaseNode :id="id" :selected="selected" label="Ответ медиа" color="pink">
        <p v-if="data.media_type" class="truncate">{{ typeLabels[data.media_type] || data.media_type }}</p>
        <p v-if="data.caption" class="truncate text-gray-500">{{ data.caption }}</p>
    </BaseNode>
</template>

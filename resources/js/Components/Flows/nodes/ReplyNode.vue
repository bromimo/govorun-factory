<script setup>
import { computed } from 'vue';
import BaseNode from './BaseNode.vue';
import { highlightVars } from './highlightVars.js';
import { stripTelegramHtml } from '@/utils/telegramHtml.js';

defineOptions({ inheritAttrs: false });

const props = defineProps(['id', 'type', 'data', 'selected']);

const typeLabels = { photo: 'Фото', video: 'Видео', audio: 'Аудио', document: 'Документ', animation: 'GIF' };

const html = computed(() => highlightVars(stripTelegramHtml(props.data.text ?? '')));

const buttonsCount = computed(() => {
    const buttons = props.data.keyboard?.buttons;
    if (!Array.isArray(buttons)) return 0;
    return buttons.flat().length;
});

const mediaPhotoUrl = computed(() => {
    const m = props.data.media;
    return m && m.type === 'photo' && m.url ? m.url : null;
});

const mediaLabel = computed(() => {
    const m = props.data.media;
    if (!m || !m.type) return null;
    if (m.type === 'photo') return null;
    return typeLabels[m.type] ?? m.type;
});
</script>

<template>
    <BaseNode :id="id" :type="type" :selected="selected" label="Ответ">
        <img v-if="mediaPhotoUrl" :src="mediaPhotoUrl" class="mb-1 max-h-24 w-full rounded object-cover" />
        <p v-else-if="mediaLabel" class="truncate text-gray-500">{{ mediaLabel }}</p>
        <p v-if="data.text" class="line-clamp-3" v-html="html"></p>
        <p v-if="buttonsCount" class="mt-1 text-gray-400">{{ buttonsCount }} кнопок</p>
    </BaseNode>
</template>
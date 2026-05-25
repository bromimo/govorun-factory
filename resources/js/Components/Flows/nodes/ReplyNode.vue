<script setup>
import { computed } from 'vue';
import BaseNode from './BaseNode.vue';
import { highlightVars } from './highlightVars.js';
import ErTooltip from '@/Components/Ui/ErTooltip.vue';
import { stripTelegramHtml } from '@/utils/telegramHtml.js';

defineOptions({ inheritAttrs: false });

const props = defineProps(['id', 'type', 'data', 'selected']);

const typeLabels = { photo: 'Фото', video: 'Видео', audio: 'Аудио', document: 'Документ', animation: 'GIF' };

const html = computed(() => highlightVars(stripTelegramHtml(props.data.text ?? '')));

const plainText = computed(() => {
    if (!props.data.text) return '';
    const div = document.createElement('div');
    div.innerHTML = props.data.text;
    return div.textContent || div.innerText || '';
});

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
        <ErTooltip v-if="data.text" :content="plainText" :max-width="360">
            <p class="line-clamp-3" v-html="html"></p>
        </ErTooltip>
        <p v-if="buttonsCount" class="mt-1 text-gray-400">{{ buttonsCount }} кнопок</p>
    </BaseNode>
</template>
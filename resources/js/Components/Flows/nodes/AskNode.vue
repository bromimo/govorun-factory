<script setup>
import { computed } from 'vue';
import BaseNode from './BaseNode.vue';
import { highlightVars } from './highlightVars.js';
import { stripTelegramHtml } from '@/utils/telegramHtml.js';

defineOptions({ inheritAttrs: false });

const props = defineProps(['id', 'data', 'selected']);

const typeLabels = { photo: 'Фото', video: 'Видео', audio: 'Аудио', document: 'Документ', animation: 'GIF' };

const html = computed(() => highlightVars(stripTelegramHtml(props.data.text ?? '')));

const isCallback = computed(() => props.data.mode === 'callback');

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

const hasValidation = computed(() => !isCallback.value && (props.data.validation?.length ?? 0) > 0);
</script>

<template>
    <BaseNode :id="id" :selected="selected" label="Вопрос" color="blue" :has-validation="hasValidation">
        <template #header-right>
            <span class="rounded bg-blue-100 px-1.5 py-0.5 text-[10px] font-semibold text-blue-700">
                {{ isCallback ? 'Кнопка' : 'Текст' }}
            </span>
        </template>
        <img v-if="mediaPhotoUrl" :src="mediaPhotoUrl" class="mb-1 max-h-24 w-full rounded object-cover" />
        <p v-else-if="mediaLabel" class="truncate text-gray-500">{{ mediaLabel }}</p>
        <p v-if="data.text" class="line-clamp-3" v-html="html"></p>
        <p v-if="isCallback && buttonsCount" class="mt-0.5 text-gray-400">{{ buttonsCount }} кнопок</p>
        <p v-if="data.stepName" class="mt-0.5 font-mono text-[10px] text-blue-400">{{ data.stepName }}</p>
    </BaseNode>
</template>
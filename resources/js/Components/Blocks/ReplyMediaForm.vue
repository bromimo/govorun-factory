<script setup>
import VarsHint from './VarsHint.vue';
import StateWarning from './StateWarning.vue';
import { useStateWarnings } from './useStateWarnings.js';

const model = defineModel({ type: Object, default: () => ({ media_type: 'photo', url: '', caption: '' }) });
const props = defineProps({
    allStateKeys: { type: Array, default: () => [] },
    declaredStateKeys: { type: Array, default: () => [] },
});

const { uninitializedKeys, undeclaredKeys } = useStateWarnings(
    () => `${model.value.url} ${model.value.caption}`, () => props.allStateKeys, () => props.declaredStateKeys,
);
</script>

<template>
    <div class="space-y-3">
        <div>
            <label class="block text-xs font-medium text-gray-500">Тип медиа</label>
            <select v-model="model.media_type" class="mt-1 w-full rounded border-gray-300 text-sm">
                <option value="photo">Фото</option>
                <option value="video">Видео</option>
                <option value="audio">Аудио</option>
                <option value="document">Документ</option>
                <option value="animation">GIF</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500">URL</label>
            <input v-model="model.url" type="text" class="mt-1 w-full rounded border-gray-300 text-sm placeholder-gray-400" placeholder="https://example.com/image.jpg" />
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500">Подпись</label>
            <textarea v-model="model.caption" rows="2" class="mt-1 w-full rounded border-gray-300 text-sm placeholder-gray-400" placeholder="Необязательно" />
            <StateWarning :uninitialized-keys="uninitializedKeys" :undeclared-keys="undeclaredKeys" />
            <VarsHint />
        </div>
    </div>
</template>

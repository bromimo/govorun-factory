<script setup>
const model = defineModel({ type: Object, default: null });

function setType(value) {
    model.value = { ...(model.value ?? { url: '' }), type: value };
}

function setUrl(value) {
    model.value = { ...(model.value ?? { type: 'photo' }), url: value };
}

function clear() {
    model.value = null;
}
</script>

<template>
    <div v-if="model" class="space-y-2 rounded border border-gray-200 bg-gray-50 p-3">
        <div class="flex items-center justify-between">
            <label class="block text-xs font-medium text-gray-500">Медиа</label>
            <button type="button" @click="clear" class="text-xs text-red-400 hover:text-red-600">
                Удалить
            </button>
        </div>

        <div>
            <label class="block text-[10px] text-gray-400">Тип</label>
            <select :value="model.type" @change="setType($event.target.value)"
                class="mt-0.5 w-full rounded border-gray-300 text-sm">
                <option value="photo">Фото</option>
                <option value="video">Видео</option>
                <option value="audio">Аудио</option>
                <option value="document">Документ</option>
                <option value="animation">GIF</option>
            </select>
        </div>

        <div>
            <label class="block text-[10px] text-gray-400">URL</label>
            <input :value="model.url" @input="setUrl($event.target.value)" type="url"
                class="mt-0.5 w-full rounded border-gray-300 text-sm placeholder-gray-400"
                placeholder="https://example.com/file" />
        </div>

        <div v-if="model.type === 'photo' && model.url" class="pt-1">
            <img :src="model.url" class="max-h-32 w-full rounded object-cover" />
        </div>
    </div>
</template>
<script setup>
import { ref, computed, watch } from 'vue';
import axios from 'axios';
import MediaLibraryModal from '@/Components/Blocks/MediaLibraryModal.vue';

const model = defineModel({ type: Object, default: null });
const props = defineProps({
    botId: { type: [Number, String], default: null },
});

const activeTab = ref('url');
const modalOpen = ref(false);
const uploading = ref(false);
const fileInputRef = ref(null);
const selectedItem = ref(null);

const typeIcons = {
    photo: '🖼',
    video: '🎬',
    audio: '🎵',
    document: '📄',
    animation: '🎞',
};

watch(
    () => model.value?.media_id,
    (id) => {
        if (!id) {
            selectedItem.value = null;
        }
    },
);

function setType(value) {
    model.value = { ...(model.value ?? { url: '' }), type: value };
    delete model.value.media_id;
    selectedItem.value = null;
}

function setUrl(value) {
    model.value = { ...(model.value ?? { type: 'photo' }), url: value };
    delete model.value.media_id;
    selectedItem.value = null;
}

function onSelect(item) {
    model.value = { type: item.type, media_id: item.id };
    selectedItem.value = item;
}

const previewUrl = computed(() => {
    if (model.value?.media_id && props.botId) {
        return route('bots.media.file', [props.botId, model.value.media_id]);
    }
    if (model.value?.url && model.value.type === 'photo') {
        return model.value.url;
    }
    return null;
});

function detectType(file) {
    const mime = file.type;
    if (mime === 'image/gif') return 'animation';
    if (mime.startsWith('image/')) return 'photo';
    if (mime.startsWith('video/')) return 'video';
    if (mime.startsWith('audio/')) return 'audio';
    return 'document';
}

async function uploadDirect(e) {
    const file = e.target.files[0];
    if (!file) {
        return;
    }
    const form = new FormData();
    form.append('file', file);
    form.append('type', detectType(file));
    uploading.value = true;
    try {
        const res = await axios.post(route('bots.media.store', props.botId), form);
        onSelect(res.data);
    } catch (err) {
        alert(err.response?.data?.message ?? 'Ошибка загрузки');
    } finally {
        uploading.value = false;
        e.target.value = '';
    }
}

function clear() {
    model.value = null;
    selectedItem.value = null;
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

        <div v-if="botId" class="inline-flex rounded-md border border-gray-300 bg-white p-0.5 text-xs">
            <button type="button" @click="activeTab = 'url'"
                :class="['rounded px-3 py-1', activeTab === 'url' ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-50']">
                URL
            </button>
            <button type="button" @click="activeTab = 'library'"
                :class="['rounded px-3 py-1', activeTab === 'library' ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-50']">
                Библиотека
            </button>
        </div>

        <template v-if="!botId || activeTab === 'url'">
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
                <input :value="model.url ?? ''" @input="setUrl($event.target.value)" type="url"
                    class="mt-0.5 w-full rounded border-gray-300 text-sm placeholder-gray-400"
                    placeholder="https://example.com/file" />
            </div>
        </template>

        <template v-else>
            <div v-if="model.media_id"
                class="flex items-center gap-2 rounded-md border border-indigo-200 bg-indigo-50 px-3 py-2">
                <span class="text-base">{{ typeIcons[model.type] ?? '📎' }}</span>
                <span class="flex-1 truncate text-xs text-indigo-700">
                    {{ selectedItem?.original_name ?? 'Файл из библиотеки' }}
                </span>
                <button type="button" @click="modalOpen = true"
                    class="shrink-0 text-xs text-indigo-500 hover:text-indigo-700">
                    Изменить
                </button>
            </div>

            <div v-else class="space-y-1.5">
                <button type="button" @click="modalOpen = true"
                    class="w-full rounded-md border border-dashed border-indigo-300 py-2.5 text-xs text-indigo-600 hover:bg-indigo-50">
                    Выбрать из библиотеки
                </button>
                <div>
                    <input ref="fileInputRef" type="file" class="hidden" @change="uploadDirect" />
                    <button type="button" @click="fileInputRef.click()" :disabled="uploading"
                        class="text-xs text-gray-500 hover:text-gray-700 disabled:opacity-50">
                        {{ uploading ? 'Загрузка…' : '+ Загрузить новый файл' }}
                    </button>
                </div>
            </div>
        </template>

        <div v-if="previewUrl" class="pt-1">
            <img :src="previewUrl" class="max-h-32 w-full rounded object-cover" />
        </div>
    </div>

    <MediaLibraryModal v-if="botId" :show="modalOpen" :bot-id="botId"
        @close="modalOpen = false" @select="onSelect" />
</template>
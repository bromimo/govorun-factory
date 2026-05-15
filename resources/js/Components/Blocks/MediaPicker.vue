<script setup>
import { ref, computed, watch } from 'vue';
import axios from 'axios';

const model = defineModel({ type: Object, default: null });
const props = defineProps({
    botId: { type: [Number, String], default: null },
});

const activeTab = ref('url');
const libraryItems = ref([]);
const libraryLoading = ref(false);
const uploading = ref(false);
const fileInputRef = ref(null);

const typeIcons = {
    photo: '🖼',
    video: '🎬',
    audio: '🎵',
    document: '📄',
    animation: '🎞',
};

function setType(value) {
    model.value = { ...(model.value ?? { url: '' }), type: value };
    delete model.value.media_id;
}

function setUrl(value) {
    model.value = { ...(model.value ?? { type: 'photo' }), url: value };
    delete model.value.media_id;
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

async function loadLibrary() {
    if (!props.botId || libraryItems.value.length) return;
    libraryLoading.value = true;
    try {
        const res = await axios.get(route('bots.media.index', props.botId));
        libraryItems.value = res.data;
    } finally {
        libraryLoading.value = false;
    }
}

watch(activeTab, (val) => {
    if (val === 'library') loadLibrary();
});

function selectFromLibrary(item) {
    model.value = { type: item.type, media_id: item.id };
}

function detectType(file) {
    const mime = file.type;
    if (mime === 'image/gif') return 'animation';
    if (mime.startsWith('image/')) return 'photo';
    if (mime.startsWith('video/')) return 'video';
    if (mime.startsWith('audio/')) return 'audio';
    return 'document';
}

async function uploadToLibrary(e) {
    const file = e.target.files[0];
    if (!file) return;
    const form = new FormData();
    form.append('file', file);
    form.append('type', detectType(file));
    uploading.value = true;
    try {
        const res = await axios.post(route('bots.media.store', props.botId), form);
        libraryItems.value.unshift(res.data);
        selectFromLibrary(res.data);
    } catch (err) {
        alert(err.response?.data?.message ?? 'Ошибка загрузки');
    } finally {
        uploading.value = false;
        e.target.value = '';
    }
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

        <!-- Tabs — only shown when botId is available -->
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

        <!-- URL mode -->
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

        <!-- Library mode -->
        <template v-else>
            <div v-if="libraryLoading" class="py-4 text-center text-xs text-gray-400">Загрузка…</div>
            <div v-else class="space-y-1.5">
                <div class="grid max-h-48 grid-cols-4 gap-1 overflow-y-auto">
                    <div v-for="item in libraryItems" :key="item.id"
                        @click="selectFromLibrary(item)"
                        :class="['flex aspect-square cursor-pointer items-center justify-center overflow-hidden rounded border-2 bg-gray-50',
                            model.media_id === item.id ? 'border-indigo-600' : 'border-transparent hover:border-gray-300']">
                        <img v-if="item.type === 'photo' || item.type === 'animation'"
                            :src="item.file_url" :alt="item.original_name"
                            class="h-full w-full object-cover" />
                        <span v-else class="text-xl">{{ typeIcons[item.type] }}</span>
                    </div>
                    <div v-if="!libraryItems.length" class="col-span-4 py-4 text-center text-xs text-gray-400">
                        Нет файлов
                    </div>
                </div>
                <div>
                    <input ref="fileInputRef" type="file" class="hidden" @change="uploadToLibrary" />
                    <button type="button" @click="fileInputRef.click()" :disabled="uploading"
                        class="text-xs text-indigo-600 hover:text-indigo-800 disabled:opacity-50">
                        {{ uploading ? 'Загрузка…' : '+ Загрузить новый' }}
                    </button>
                </div>
            </div>
        </template>

        <!-- Preview -->
        <div v-if="previewUrl" class="pt-1">
            <img :src="previewUrl" class="max-h-32 w-full rounded object-cover" />
        </div>
    </div>
</template>

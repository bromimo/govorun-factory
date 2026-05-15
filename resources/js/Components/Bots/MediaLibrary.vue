<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({
    bot: { type: Object, required: true },
    initialItems: { type: Array, default: () => [] },
});

const items = ref(props.initialItems.length ? [...props.initialItems] : []);
const loading = ref(false);
const uploading = ref(false);
const fileInput = ref(null);

const typeIcons = {
    photo: '🖼',
    video: '🎬',
    audio: '🎵',
    document: '📄',
    animation: '🎞',
};

function formatSize(bytes) {
    if (bytes < 1024 * 1024) {
        return (bytes / 1024).toFixed(0) + ' КБ';
    }
    return (bytes / (1024 * 1024)).toFixed(1) + ' МБ';
}

async function load() {
    loading.value = true;
    try {
        const res = await axios.get(route('bots.media.index', props.bot.id));
        items.value = res.data;
    } finally {
        loading.value = false;
    }
}

function detectType(file) {
    const mime = file.type;
    if (mime === 'image/gif') return 'animation';
    if (mime.startsWith('image/')) return 'photo';
    if (mime.startsWith('video/')) return 'video';
    if (mime.startsWith('audio/')) return 'audio';
    return 'document';
}

async function handleFiles(files) {
    uploading.value = true;
    for (const file of Array.from(files)) {
        const form = new FormData();
        form.append('file', file);
        form.append('type', detectType(file));
        try {
            const res = await axios.post(route('bots.media.store', props.bot.id), form);
            items.value.unshift(res.data);
        } catch (e) {
            alert(e.response?.data?.message ?? `Ошибка загрузки: ${file.name}`);
        }
    }
    uploading.value = false;
    if (fileInput.value) fileInput.value.value = '';
}

async function remove(item) {
    if (!confirm(`Удалить «${item.original_name}»?`)) return;
    await axios.delete(route('bots.media.destroy', [props.bot.id, item.id]));
    items.value = items.value.filter(i => i.id !== item.id);
}

onMounted(() => {
    if (!items.value.length) load();
});
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <span class="text-sm text-gray-500">{{ items.length }} файл(ов)</span>
            <div>
                <input ref="fileInput" type="file" multiple class="hidden"
                    @change="handleFiles($event.target.files)" />
                <button type="button" @click="fileInput.click()" :disabled="uploading"
                    class="rounded-md bg-indigo-600 px-3 py-1.5 text-sm text-white hover:bg-indigo-500 disabled:opacity-50">
                    {{ uploading ? 'Загрузка…' : '+ Загрузить' }}
                </button>
            </div>
        </div>

        <div v-if="loading" class="py-8 text-center text-sm text-gray-400">Загрузка…</div>

        <div v-else-if="!items.length"
            class="rounded-lg border-2 border-dashed border-gray-300 py-12 text-center text-sm text-gray-400">
            Нет загруженных файлов
        </div>

        <div v-else class="grid grid-cols-3 gap-3 sm:grid-cols-4 md:grid-cols-5">
            <div v-for="item in items" :key="item.id"
                class="group relative overflow-hidden rounded-lg border border-gray-200 bg-white">
                <div class="flex aspect-square items-center justify-center bg-gray-50">
                    <img v-if="item.type === 'photo' || item.type === 'animation'"
                        :src="item.file_url" :alt="item.original_name"
                        class="h-full w-full object-cover" />
                    <span v-else class="text-3xl">{{ typeIcons[item.type] }}</span>
                </div>
                <div class="p-1.5">
                    <p class="truncate text-xs text-gray-600">{{ item.original_name }}</p>
                    <p class="text-xs text-gray-400">{{ formatSize(item.size) }}</p>
                </div>
                <button type="button" @click="remove(item)"
                    class="absolute right-1 top-1 hidden rounded bg-red-500 px-1 py-0.5 text-xs text-white group-hover:block">
                    ×
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import axios from 'axios';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    botId: { type: [Number, String], required: true },
});

const emit = defineEmits(['close', 'select']);

const items = ref([]);
const loading = ref(false);
const uploading = ref(false);
const activeFilter = ref('all');
const tempSelected = ref(null);
const fileInputRef = ref(null);

const filters = [
    { key: 'all', label: 'Все' },
    { key: 'photo', label: 'Фото' },
    { key: 'video', label: 'Видео' },
    { key: 'audio', label: 'Аудио' },
    { key: 'animation', label: 'GIF' },
    { key: 'document', label: 'Документ' },
];

const typeIcons = {
    photo: '🖼',
    video: '🎬',
    audio: '🎵',
    document: '📄',
    animation: '🎞',
};

const filtered = computed(() => {
    if (activeFilter.value === 'all') {
        return items.value;
    }
    return items.value.filter((i) => i.type === activeFilter.value);
});

watch(
    () => props.show,
    async (val) => {
        if (!val) {
            return;
        }
        tempSelected.value = null;
        if (!items.value.length) {
            await load();
        }
    },
);

async function load() {
    loading.value = true;
    try {
        const res = await axios.get(route('bots.media.index', props.botId));
        items.value = res.data;
    } finally {
        loading.value = false;
    }
}

function confirm() {
    if (!tempSelected.value) {
        return;
    }
    emit('select', tempSelected.value);
    emit('close');
}

function detectType(file) {
    const mime = file.type;
    if (mime === 'image/gif') return 'animation';
    if (mime.startsWith('image/')) return 'photo';
    if (mime.startsWith('video/')) return 'video';
    if (mime.startsWith('audio/')) return 'audio';
    return 'document';
}

async function upload(e) {
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
        items.value.unshift(res.data);
        tempSelected.value = res.data;
    } catch (err) {
        alert(err.response?.data?.message ?? 'Ошибка загрузки');
    } finally {
        uploading.value = false;
        e.target.value = '';
    }
}
</script>

<template>
    <Modal :show="show" max-width="screen" @close="emit('close')">
        <div class="flex max-h-[90vh] flex-col">
            <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                <h3 class="text-lg font-medium text-gray-900">Медиатека</h3>
                <button type="button" @click="emit('close')"
                    class="text-gray-400 hover:text-gray-600">✕</button>
            </div>

            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-3">
                <div class="flex flex-wrap gap-1">
                    <button v-for="f in filters" :key="f.key" type="button"
                        @click="activeFilter = f.key"
                        :class="['rounded-full px-3 py-1 text-xs font-medium transition',
                            activeFilter === f.key
                                ? 'bg-indigo-600 text-white'
                                : 'bg-gray-100 text-gray-600 hover:bg-gray-200']">
                        {{ f.label }}
                    </button>
                </div>
                <div class="ml-3 shrink-0">
                    <input ref="fileInputRef" type="file" class="hidden" @change="upload" />
                    <button type="button" @click="fileInputRef.click()" :disabled="uploading"
                        class="rounded-md bg-white px-3 py-1.5 text-xs font-medium text-indigo-600 ring-1 ring-indigo-300 hover:bg-indigo-50 disabled:opacity-50">
                        {{ uploading ? 'Загрузка…' : '+ Загрузить' }}
                    </button>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto p-4">
                <div v-if="loading"
                    class="flex h-48 items-center justify-center text-sm text-gray-400">
                    Загрузка…
                </div>
                <div v-else-if="!filtered.length"
                    class="flex h-48 items-center justify-center text-sm text-gray-400">
                    Нет файлов
                </div>
                <div v-else class="grid grid-cols-5 gap-2 sm:grid-cols-6">
                    <button v-for="item in filtered" :key="item.id" type="button"
                        @click="tempSelected = item"
                        @dblclick="() => { tempSelected = item; confirm(); }"
                        :class="['flex flex-col items-center overflow-hidden rounded-lg border-2 p-1 transition',
                            tempSelected?.id === item.id
                                ? 'border-indigo-600 bg-indigo-50'
                                : 'border-transparent bg-gray-50 hover:border-gray-300']">
                        <div class="flex h-20 w-full items-center justify-center overflow-hidden rounded bg-gray-100">
                            <img v-if="item.type === 'photo' || item.type === 'animation'"
                                :src="item.file_url" :alt="item.original_name"
                                class="h-full w-full object-cover" />
                            <span v-else class="text-3xl">{{ typeIcons[item.type] }}</span>
                        </div>
                        <p class="mt-1 w-full truncate text-center text-[10px] text-gray-600"
                            :title="item.original_name">
                            {{ item.original_name }}
                        </p>
                    </button>
                </div>
            </div>

            <div class="flex items-center justify-between border-t border-gray-200 px-6 py-4">
                <span v-if="tempSelected" class="truncate text-xs text-gray-500">
                    Выбрано: {{ tempSelected.original_name }}
                </span>
                <span v-else class="text-xs text-gray-400">
                    Кликните на файл для выбора, двойной клик — сразу вставить
                </span>
                <div class="ml-4 flex shrink-0 gap-3">
                    <button type="button" @click="emit('close')"
                        class="rounded-md bg-white px-4 py-2 text-sm font-medium text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50">
                        Отмена
                    </button>
                    <button type="button" @click="confirm" :disabled="!tempSelected"
                        class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500 disabled:opacity-50">
                        Выбрать
                    </button>
                </div>
            </div>
        </div>
    </Modal>
</template>
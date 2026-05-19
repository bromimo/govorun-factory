<script setup>
import { ref, computed, watch } from 'vue';
import axios from 'axios';
import Modal from '@/Components/Modal.vue';
import ErButton from '@/Components/Ui/ErButton.vue';

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

const extPalette = {
    jpg: '#d97706', jpeg: '#d97706', png: '#0284c7', webp: '#0891b2', gif: '#16a34a',
    mp4: '#2563eb', mov: '#2563eb', avi: '#1d4ed8', mkv: '#1d4ed8', webm: '#4f46e5',
    mp3: '#dc2626', ogg: '#dc2626', wav: '#b91c1c', m4a: '#dc2626', pdf: '#ef4444',
};

function extColor(filename) {
    const ext = filename.split('.').pop().toLowerCase();
    return extPalette[ext] ?? '#6b7280';
}

const filtered = computed(() => {
    if (activeFilter.value === 'all') {
        return items.value;
    }
    return items.value.filter((i) => i.type === activeFilter.value);
});

watch(() => props.show, async (val) => {
    if (!val) {
        return;
    }
    tempSelected.value = null;
    if (!items.value.length) {
        await load();
    }
});

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
    <Modal :show="show" title="Медиатека" max-width="screen" @close="emit('close')">
        <div class="ml-root">
            <div class="ml-toolbar">
                <div class="ml-filters">
                    <button
                        v-for="f in filters"
                        :key="f.key"
                        type="button"
                        class="ml-filter-btn"
                        :class="{ act: activeFilter === f.key }"
                        @click="activeFilter = f.key"
                    >{{ f.label }}</button>
                </div>
                <div>
                    <input ref="fileInputRef" type="file" style="display:none" @change="upload" />
                    <ErButton size="sm" @click="fileInputRef.click()" :disabled="uploading">
                        {{ uploading ? 'Загрузка…' : '+ Загрузить' }}
                    </ErButton>
                </div>
            </div>

            <div class="ml-grid-wrap">
                <div v-if="loading" class="ml-empty">Загрузка…</div>
                <div v-else-if="!filtered.length" class="ml-empty">Нет файлов</div>
                <div v-else class="ml-grid">
                    <button
                        v-for="item in filtered"
                        :key="item.id"
                        type="button"
                        class="ml-item"
                        :class="{ sel: tempSelected?.id === item.id }"
                        @click="tempSelected = item"
                        @dblclick="() => { tempSelected = item; confirm(); }"
                    >
                        <div class="ml-item-top">
                            <span class="ml-ext" :style="{ background: extColor(item.original_name) }">
                                {{ item.original_name.split('.').pop() }}
                            </span>
                        </div>
                        <div class="ml-item-thumb">
                            <img v-if="item.type === 'photo' || item.type === 'animation'"
                                :src="item.file_url" :alt="item.original_name"
                                style="width:100%;height:100%;object-fit:contain" />
                            <span v-else class="ml-item-icon">{{ typeIcons[item.type] }}</span>
                        </div>
                        <p class="ml-item-name" :title="item.original_name">
                            {{ item.original_name.replace(/\.[^.]+$/, '') }}
                        </p>
                    </button>
                </div>
            </div>

            <div class="ml-foot">
                <span v-if="tempSelected" class="ml-foot-sel">
                    Выбрано: {{ tempSelected.original_name }}
                </span>
                <span v-else class="ml-foot-hint">
                    Кликните на файл для выбора, двойной клик — сразу вставить
                </span>
                <div class="ml-foot-acts">
                    <ErButton @click="emit('close')">Отмена</ErButton>
                    <ErButton variant="primary" @click="confirm" :disabled="!tempSelected">Выбрать</ErButton>
                </div>
            </div>
        </div>
    </Modal>
</template>

<style scoped>
.ml-root {
    display: flex;
    flex-direction: column;
    height: 80vh;
    min-height: 0;
    overflow: hidden;
}
.ml-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 12px;
    border-bottom: 1px solid var(--bdr-l);
    flex-shrink: 0;
    gap: 8px;
    background: var(--surface-2);
}
.ml-filters { display: flex; flex-wrap: wrap; gap: 4px; }
.ml-filter-btn {
    padding: 2px 10px;
    height: 22px;
    font-size: 11px;
    font-weight: 500;
    border-radius: 11px;
    border: 1px solid var(--bdr);
    background: var(--surface);
    color: var(--ink-2);
    cursor: pointer;
    transition: background .1s, color .1s, border-color .1s;
    font-family: var(--font);
}
.ml-filter-btn:hover { background: var(--surface-3); }
.ml-filter-btn.act { background: var(--blue); border-color: var(--blue-d); color: #fff; }

.ml-grid-wrap {
    flex: 1;
    overflow-y: auto;
    padding: 10px;
    min-height: 0;
}
.ml-empty {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 160px;
    color: var(--ink-4);
    font-size: 12px;
}
.ml-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
    gap: 6px;
}
.ml-item {
    display: flex;
    flex-direction: column;
    overflow: hidden;
    border-radius: var(--r-sm);
    border: 2px solid transparent;
    background: var(--surface);
    cursor: pointer;
    transition: border-color .1s;
    text-align: left;
    padding: 0;
}
.ml-item:hover { border-color: var(--bdr-d); }
.ml-item.sel { border-color: var(--blue); }

.ml-item-top {
    display: flex;
    justify-content: flex-end;
    background: var(--surface-2);
    padding: 2px 4px;
}
.ml-ext {
    border-radius: 2px;
    padding: 1px 4px;
    font-size: 9px;
    font-weight: 700;
    text-transform: uppercase;
    color: #fff;
    font-family: var(--mono);
    line-height: 1.4;
}
.ml-item-thumb {
    aspect-ratio: 1;
    overflow: hidden;
    background: var(--surface-3);
    display: flex;
    align-items: center;
    justify-content: center;
}
.ml-item-icon { font-size: 28px; }
.ml-item-name {
    padding: 3px 5px;
    font-size: 10px;
    color: var(--ink-2);
    text-overflow: ellipsis;
    overflow: hidden;
    white-space: nowrap;
    background: var(--surface-2);
    margin: 0;
}

.ml-foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 14px;
    border-top: 1px solid var(--bdr);
    background: linear-gradient(180deg, #f4f6f8 0%, #e8ecf0 100%);
    flex-shrink: 0;
    gap: 12px;
}
.ml-foot-sel { font-size: 12px; color: var(--ink-2); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 400px; }
.ml-foot-hint { font-size: 11px; color: var(--ink-4); }
.ml-foot-acts { display: flex; gap: 8px; flex-shrink: 0; }
</style>
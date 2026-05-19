<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import ErButton from '@/Components/Ui/ErButton.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    bot: { type: Object, required: true },
    initialItems: { type: Array, default: () => [] },
});

const items = ref(props.initialItems.length ? [...props.initialItems] : []);
const loading = ref(false);
const uploading = ref(false);
const fileInput = ref(null);
const dragOver = ref(false);
const confirmItem = ref(null);

const extColor = {
    // фото — тёплые оттенки
    jpg:  '#e07820', jpeg: '#e07820',
    png:  '#2a7ab5',
    webp: '#1a8a7a',
    heic: '#c06818', heif: '#c06818',
    tiff: '#8a4ab5', tif: '#8a4ab5',
    bmp:  '#7a8a7a',
    svg:  '#e84a2a',
    // RAW — красные
    raw: '#b03030', cr2: '#b03030', cr3: '#b03030',
    nef: '#b03030', arw: '#b03030', dng: '#b03030',
    orf: '#b03030', rw2: '#b03030',
    // GIF / анимация
    gif:  '#2a9a3a',
    // видео — фиолетовые
    mp4: '#7c3aed', mov: '#7c3aed', avi: '#6a2acd',
    mkv: '#5a2acd', webm: '#7a3acd', wmv: '#6a2acd',
    // аудио — янтарные
    mp3: '#c87820', wav: '#b86010',
    ogg: '#a85010', flac: '#986000', aac: '#c87820',
    // документы
    pdf:  '#c03030',
    doc:  '#2a5ca0', docx: '#2a5ca0',
    xls:  '#2a7a3a', xlsx: '#2a7a3a',
    ppt:  '#c05820', pptx: '#c05820',
    txt:  '#7a8a9a', csv: '#2a7a3a',
    zip:  '#8a6a2a', rar: '#8a6a2a', '7z': '#8a6a2a',
};

const typeFallback = {
    photo: '#5a7a9a', animation: '#2a9a3a',
    video: '#7c3aed', audio: '#c87820', document: '#3a72c4',
};

function pluralFiles(n) {
    const mod10 = n % 10;
    const mod100 = n % 100;
    if (mod10 === 1 && mod100 !== 11) return `${n} файл`;
    if (mod10 >= 2 && mod10 <= 4 && (mod100 < 10 || mod100 >= 20)) return `${n} файла`;
    return `${n} файлов`;
}

function fileExt(name) {
    const m = name?.match(/\.([^.]+)$/);
    return m ? m[1].toUpperCase() : '?';
}

function badgeColor(item) {
    const ext = item.original_name?.match(/\.([^.]+)$/)?.[1]?.toLowerCase();
    return extColor[ext] ?? typeFallback[item.type] ?? '#7a8a9a';
}

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

function remove(item) {
    confirmItem.value = item;
}

async function confirmRemove() {
    const item = confirmItem.value;
    confirmItem.value = null;
    await axios.delete(route('bots.media.destroy', [props.bot.id, item.id]));
    items.value = items.value.filter(i => i.id !== item.id);
}

function onDrop(e) {
    dragOver.value = false;
    const files = e.dataTransfer?.files;
    if (files?.length) handleFiles(files);
}

onMounted(() => {
    if (!items.value.length) load();
});
</script>

<template>
    <div class="ml-root">
        <div class="ml-toolbar">
            <span class="ml-count">{{ pluralFiles(items.length) }}</span>
            <div style="flex: 1;" />
            <input ref="fileInput" type="file" multiple style="display: none;"
                @change="handleFiles($event.target.files)" />
            <ErButton variant="primary" size="sm" :disabled="uploading" @click="fileInput.click()">
                {{ uploading ? 'Загрузка…' : '+ Загрузить' }}
            </ErButton>
        </div>

        <div v-if="loading" class="ml-state">Загрузка…</div>

        <div v-else-if="!items.length"
            class="ml-drop-zone"
            :class="{ over: dragOver }"
            @dragover.prevent="dragOver = true"
            @dragleave="dragOver = false"
            @drop.prevent="onDrop"
            @click="fileInput.click()"
        >
            <div class="ml-drop-icon">⊕</div>
            <div class="ml-drop-text">Перетащите файлы или нажмите для загрузки</div>
        </div>

        <div v-else
            class="ml-grid"
            :class="{ over: dragOver }"
            @dragover.prevent="dragOver = true"
            @dragleave="dragOver = false"
            @drop.prevent="onDrop"
        >
            <div v-for="item in items" :key="item.id" class="ml-card" :title="item.original_name">
                <div class="ml-card-header">
                    <span class="ml-type-badge" :style="{ background: badgeColor(item) }">
                        {{ fileExt(item.original_name) }}
                    </span>
                    <div style="flex: 1;" />
                    <button class="ml-del-btn" type="button" @click.stop="remove(item)" title="Удалить">×</button>
                </div>
                <div class="ml-thumb">
                    <img v-if="item.type === 'photo' || item.type === 'animation'"
                        :src="item.file_url" :alt="item.original_name" />
                    <span v-else class="ml-thumb-icon">
                        {{ { video:'🎬', audio:'🎵', document:'📄', animation:'🎞' }[item.type] }}
                    </span>
                </div>
                <div class="ml-caption">
                    <span class="ml-name">{{ item.original_name }}</span>
                    <span class="ml-size">{{ formatSize(item.size) }}</span>
                </div>
            </div>
        </div>
    </div>

    <Modal :show="!!confirmItem" title="Удалить файл?" max-width="sm" @close="confirmItem = null">
        <div class="del-body">
            <p class="del-desc">Файл <strong>{{ confirmItem?.original_name }}</strong> будет удалён безвозвратно.</p>
        </div>
        <div class="del-foot">
            <ErButton @click="confirmItem = null">Отмена</ErButton>
            <ErButton variant="danger" @click="confirmRemove">Удалить</ErButton>
        </div>
    </Modal>
</template>

<style scoped>
.ml-root {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.ml-toolbar {
    display: flex;
    align-items: center;
    gap: 8px;
}
.ml-count { font-size: 11px; color: var(--ink-3); }

/* Drop zone (empty state) */
.ml-drop-zone {
    border: 2px dashed var(--bdr-d);
    border-radius: var(--r-md);
    padding: 48px 24px;
    text-align: center;
    cursor: pointer;
    color: var(--ink-3);
    transition: background .15s, border-color .15s;
}
.ml-drop-zone:hover, .ml-drop-zone.over {
    background: var(--blue-soft);
    border-color: var(--blue);
    color: var(--blue-d);
}
.ml-drop-icon { font-size: 32px; margin-bottom: 8px; }
.ml-drop-text { font-size: 12px; }

/* Grid */
.ml-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
    gap: 4px;
    padding: 2px;
    border-radius: var(--r-md);
    transition: background .15s;
}
.ml-grid.over { background: var(--blue-soft); }

/* Card */
.ml-card {
    display: flex;
    flex-direction: column;
    border: 1px solid var(--bdr);
    border-radius: 3px;
    overflow: hidden;
    background: var(--blue-soft);
    cursor: default;
    transition: border-color .12s, box-shadow .12s;
}
.ml-card:hover { border-color: var(--blue); box-shadow: 0 1px 4px rgba(58,114,196,.15); }

/* Top header strip: badge + delete */
.ml-card-header {
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 3px 4px;
    background: var(--blue-soft);
    border-bottom: 1px solid var(--bdr-l);
    min-height: 20px;
    flex-shrink: 0;
}

/* Type badge */
.ml-type-badge {
    font-size: 9px;
    font-weight: 700;
    color: #fff;
    padding: 1px 5px;
    border-radius: 2px;
    letter-spacing: .04em;
    flex-shrink: 0;
}

/* Delete button — visible on card hover */
.ml-del-btn {
    width: 16px;
    height: 16px;
    border-radius: 2px;
    background: transparent;
    color: var(--ink-4);
    border: none;
    cursor: pointer;
    font-size: 15px;
    line-height: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: background .1s, color .1s;
    opacity: 0;
}
.ml-card:hover .ml-del-btn { opacity: 1; }
.ml-del-btn:hover { background: var(--red-soft, #fde8e8); color: var(--red) !important; }

/* Thumbnail area — flex:1 pushes caption to bottom */
.ml-thumb {
    flex: 1;
    background: var(--blue-soft);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 3px;
    min-height: 70px;
}
.ml-thumb img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
    display: block;
}
.ml-thumb-icon { font-size: 28px; }

/* Caption strip — sticks to bottom */
.ml-caption {
    padding: 3px 4px 4px;
    background: var(--blue-soft);
    border-top: 1px solid var(--bdr-l);
    display: flex;
    flex-direction: column;
    gap: 1px;
    min-width: 0;
    flex-shrink: 0;
}
.ml-name {
    font-size: 10px;
    color: var(--ink-2);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    font-family: var(--mono);
}
.ml-size {
    font-size: 9px;
    color: var(--ink-4);
    font-family: var(--mono);
}

.del-body { padding: 16px 20px; }
.del-desc { font-size: 12px; color: var(--ink-2); margin: 0; }
.del-desc strong { color: var(--ink); font-weight: 600; word-break: break-all; }
.del-foot { display: flex; justify-content: flex-end; gap: 8px; padding: 10px 20px 14px; border-top: 1px solid var(--bdr-l); background: linear-gradient(180deg, #f4f6f8 0%, #e8ecf0 100%); }
</style>

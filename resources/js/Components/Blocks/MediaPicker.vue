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
    <div v-if="model" class="mp-card">
        <div class="mp-header">
            <label class="field-lbl">Медиа</label>
            <button type="button" @click="clear" class="field-del">Удалить</button>
        </div>

        <div v-if="botId" class="toggle-group">
            <button type="button" @click="activeTab = 'url'"
                :class="['toggle-btn', activeTab === 'url' ? 'is-on' : '']">
                URL
            </button>
            <button type="button" @click="activeTab = 'library'"
                :class="['toggle-btn', activeTab === 'library' ? 'is-on' : '']">
                Библиотека
            </button>
        </div>

        <template v-if="!botId || activeTab === 'url'">
            <div>
                <label class="field-sub-lbl">Тип</label>
                <select :value="model.type" @change="setType($event.target.value)" class="field-sel mt-1">
                    <option value="photo">Фото</option>
                    <option value="video">Видео</option>
                    <option value="audio">Аудио</option>
                    <option value="document">Документ</option>
                    <option value="animation">GIF</option>
                </select>
            </div>
            <div>
                <label class="field-sub-lbl">URL</label>
                <input :value="model.url ?? ''" @input="setUrl($event.target.value)" type="url"
                    class="field-input mt-1"
                    placeholder="https://example.com/file" />
            </div>
        </template>

        <template v-else>
            <div v-if="model.media_id" class="mp-selected">
                <span class="mp-type-icon">{{ typeIcons[model.type] ?? '📎' }}</span>
                <span class="mp-filename">
                    {{ selectedItem?.original_name ?? 'Файл из библиотеки' }}
                </span>
                <button type="button" @click="modalOpen = true" class="field-link shrink-0">
                    Изменить
                </button>
            </div>

            <div v-else class="mp-empty">
                <button type="button" @click="modalOpen = true" class="mp-pick-btn">
                    Выбрать из библиотеки
                </button>
                <div>
                    <input ref="fileInputRef" type="file" class="hidden" @change="uploadDirect" />
                    <button type="button" @click="fileInputRef.click()" :disabled="uploading"
                        class="field-link" style="opacity: var(--uploading, 1);"
                        :style="{ opacity: uploading ? .5 : 1 }">
                        {{ uploading ? 'Загрузка…' : '+ Загрузить новый файл' }}
                    </button>
                </div>
            </div>
        </template>

        <div v-if="previewUrl" class="mp-preview">
            <img :src="previewUrl" class="mp-img" />
        </div>
    </div>

    <MediaLibraryModal v-if="botId" :show="modalOpen" :bot-id="botId"
        @close="modalOpen = false" @select="onSelect" />
</template>

<style scoped>
.mp-card {
    display: flex;
    flex-direction: column;
    gap: 8px;
    border: 1px solid var(--bdr);
    border-radius: var(--r-sm);
    background: var(--surface-2);
    padding: 10px;
}
.mp-header { display: flex; align-items: center; justify-content: space-between; }
.field-lbl { display: block; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; color: var(--ink-3); }
.field-sub-lbl { display: block; font-size: 10px; color: var(--ink-4); }
.field-del { font-size: 11px; color: var(--red); background: none; border: none; cursor: pointer; padding: 0; font-family: var(--font); }
.field-del:hover { opacity: .8; }
.field-link { font-size: 12px; color: var(--blue); background: none; border: none; cursor: pointer; padding: 0; font-family: var(--font); }
.field-link:hover { text-decoration: underline; }
.toggle-group { display: inline-flex; border: 1px solid var(--bdr-d); border-radius: var(--r-sm); background: var(--surface); padding: 2px; }
.toggle-btn { padding: 2px 10px; border-radius: calc(var(--r-sm) - 1px); font-size: 11px; color: var(--ink-2); background: none; border: none; cursor: pointer; font-family: var(--font); }
.toggle-btn.is-on { background: var(--blue); color: #fff; }
.toggle-btn:not(.is-on):hover { background: var(--surface-2); }
.field-input, .field-sel {
    height: 26px;
    padding: 0 8px;
    border: 1px solid var(--bdr-d);
    border-radius: var(--r-sm);
    background: #fff;
    color: var(--ink);
    font-size: 12px;
    font-family: var(--font);
    width: 100%;
    box-sizing: border-box;
    display: block;
}
.field-input:focus, .field-sel:focus { outline: none; border-color: var(--blue); box-shadow: 0 0 0 2px rgba(58,114,196,.2); }
.mt-1 { margin-top: 4px; }
.mp-selected {
    display: flex;
    align-items: center;
    gap: 8px;
    border: 1px solid var(--bdr);
    border-radius: var(--r-sm);
    padding: 5px 8px;
    background: var(--blue-soft);
}
.mp-type-icon { font-size: 14px; }
.mp-filename { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 12px; color: var(--ink-2); }
.mp-empty { display: flex; flex-direction: column; gap: 6px; }
.mp-pick-btn {
    width: 100%;
    border-radius: var(--r-sm);
    border: 1px dashed var(--bdr-d);
    padding: 8px 10px;
    font-size: 12px;
    color: var(--blue);
    background: var(--surface);
    cursor: pointer;
    font-family: var(--font);
    text-align: center;
    transition: background .1s;
}
.mp-pick-btn:hover { background: var(--blue-soft); }
.mp-preview { padding-top: 4px; }
.mp-img { max-height: 120px; width: 100%; border-radius: var(--r-sm); object-fit: cover; }
.hidden { display: none; }
</style>
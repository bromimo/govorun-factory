<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import draggable from 'vuedraggable';

const model = defineModel({ type: Object });
const props = defineProps({ botId: [Number, String] });

function parsePairs(queryString) {
    if (!queryString) return [];
    return queryString.split('&')
        .map(p => {
            const idx = p.indexOf('=');
            return idx === -1
                ? { key: p, value: '' }
                : { key: p.slice(0, idx), value: p.slice(idx + 1) };
        })
        .filter(p => p.key !== '');
}

function buildQueryString(pairs) {
    const filled = pairs.filter(p => p.key !== '');
    if (!filled.length) return '';
    return '?' + filled.map(p => p.key + (p.value !== '' ? '=' + p.value : '')).join('&');
}

let syncing = false;
const strippedDomain = ref(null);

watch(() => model.value?.path, (newPath) => {
    if (syncing) return;
    let path = newPath ?? '';
    if (/^https?:\/\//i.test(path)) {
        try {
            const u = new URL(path);
            const pastedHost = u.host;
            let connectionHost = null;
            let connectionBasePath = '';
            try {
                const connUrl = new URL(selectedConnection.value?.base_url ?? '');
                connectionHost = connUrl.host;
                connectionBasePath = connUrl.pathname.replace(/\/$/, '');
            } catch {}
            path = u.pathname + u.search;
            if (connectionBasePath && path.startsWith(connectionBasePath + '/')) {
                path = path.slice(connectionBasePath.length);
            }
            strippedDomain.value = (!connectionHost || pastedHost !== connectionHost) ? pastedHost : null;
        } catch {
            strippedDomain.value = null;
        }
    } else {
        strippedDomain.value = null;
    }
    const qIdx = path.indexOf('?');
    const qs = qIdx === -1 ? '' : path.slice(qIdx + 1);
    syncing = true;
    if (path !== newPath) model.value.path = path;
    model.value.query = parsePairs(qs);
    syncing = false;
}, { flush: 'sync', immediate: true });

watch(() => model.value?.query, (newQuery) => {
    if (syncing) return;
    const pathOnly = (model.value.path ?? '').split('?')[0];
    syncing = true;
    model.value.path = pathOnly + buildQueryString(newQuery);
    syncing = false;
}, { deep: true, flush: 'sync' });

const connections = ref([]);

onMounted(async () => {
    const r = await fetch(route('bot-connections.index', props.botId), {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
    });
    if (r.ok) {
        const data = await r.json();
        connections.value = data.connections ?? data;
    }
});

const selectedConnection = computed(() =>
    connections.value.find(c => c.id === model.value.connection_id) ?? null,
);

const fullUrlPreview = computed(() => {
    if (! selectedConnection.value) return '';
    return (selectedConnection.value.base_url ?? '').replace(/\/$/, '')
        + '/' + (model.value.path ?? '').replace(/^\//, '');
});

const urlPreviewParts = computed(() => {
    if (! fullUrlPreview.value) return [];
    const [base, qs] = fullUrlPreview.value.split('?');
    if (! qs) return [base];
    return [base, ...qs.split('&').map((p, i) => (i === 0 ? '?' : '&') + p)];
});

function addPair(key) {
    model.value[key].push({ key: '', value: '' });
}
function removePair(key, i) {
    model.value[key].splice(i, 1);
}

const pairIds = new WeakMap();
function getPairId(pair) {
    if (!pairIds.has(pair)) {
        pairIds.set(pair, Math.random());
    }
    return pairIds.get(pair);
}
</script>

<template>
    <div class="rs-wrap">
        <div>
            <label class="field-lbl">Подключение</label>
            <div class="rs-conn-row">
                <select v-model="model.connection_id" class="field-sel flex-1">
                    <option :value="null">— выбрать —</option>
                    <option v-for="c in connections" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
                <a :href="route('bot-connections.index', botId)" target="_blank" class="er-btn sm">+ новое</a>
            </div>
        </div>

        <div class="rs-method-row">
            <select v-model="model.method" class="field-sel method-sel">
                <option>GET</option><option>POST</option><option>PUT</option><option>DELETE</option><option>PATCH</option>
            </select>
            <input v-model="model.path" type="text" placeholder="/users/{{state.user_id}}"
                class="field-input mono flex-1" />
        </div>
        <div v-if="strippedDomain" class="rs-warn">
            ⚠ Домен {{ strippedDomain }} проигнорирован — используется домен из подключения.
        </div>
        <div v-if="fullUrlPreview" class="rs-preview">
            <div>→ {{ urlPreviewParts[0] }}</div>
            <div v-for="(part, i) in urlPreviewParts.slice(1)" :key="i" class="rs-preview-cont">{{ part }}</div>
        </div>

        <details class="rs-details">
            <summary class="rs-summary">Параметры запроса ({{ model.query.length }})</summary>
            <draggable v-model="model.query" :item-key="getPairId" handle=".drag-handle" :animation="150">
                <template #item="{ element: p, index: i }">
                    <div class="rs-pair">
                        <span class="drag-handle rs-drag">⠿</span>
                        <input v-model="p.key" placeholder="name" class="field-input flex-1" />
                        <input v-model="p.value" placeholder="value" class="field-input flex-1" />
                        <button type="button" @click="removePair('query', i)" class="rs-del">✕</button>
                    </div>
                </template>
            </draggable>
            <button type="button" @click="addPair('query')" class="er-btn sm rs-add">+ параметр</button>
        </details>

        <details class="rs-details">
            <summary class="rs-summary">Заголовки ({{ model.headers.length }})</summary>
            <div v-for="(h, i) in model.headers" :key="i" class="rs-pair">
                <input v-model="h.key" placeholder="Header" class="field-input flex-1" />
                <input v-model="h.value" placeholder="Value" class="field-input flex-1" />
                <button type="button" @click="removePair('headers', i)" class="rs-del">✕</button>
            </div>
            <button type="button" @click="addPair('headers')" class="er-btn sm rs-add">+ заголовок</button>
        </details>

        <details class="rs-details" :open="model.body_mode !== 'none'">
            <summary class="rs-summary">Тело запроса</summary>
            <div class="rs-body-modes">
                <label class="rs-radio"><input type="radio" v-model="model.body_mode" value="none" /> Нет</label>
                <label class="rs-radio"><input type="radio" v-model="model.body_mode" value="json" /> JSON</label>
                <label class="rs-radio"><input type="radio" v-model="model.body_mode" value="form" /> Form data</label>
            </div>
            <textarea v-if="model.body_mode === 'json'" v-model="model.body" rows="6"
                class="field-ta mono"
                placeholder='{"fields": {"NAME": "{{state.name}}"}}' />
            <div v-else-if="model.body_mode === 'form'">
                <div v-for="(p, i) in (model.body ?? [])" :key="i" class="rs-pair">
                    <input v-model="p.key" class="field-input flex-1" />
                    <input v-model="p.value" class="field-input flex-1" />
                </div>
                <button type="button" @click="(model.body ??= []).push({key:'', value:''})" class="er-btn sm rs-add">+ поле</button>
            </div>
        </details>
    </div>
</template>

<style scoped>
.rs-wrap { display: flex; flex-direction: column; gap: 10px; }
.rs-conn-row { display: flex; gap: 8px; align-items: center; }
.rs-method-row { display: grid; grid-template-columns: 90px 1fr; gap: 6px; }
.method-sel { width: 100%; }
.field-ta { margin-top: 6px; }
.rs-warn { font-size: 11px; color: var(--orange); }
.rs-preview { font-size: 11px; font-family: var(--mono, monospace); color: var(--ink-4); }
.rs-preview-cont { padding-left: 14px; }
.rs-details { border: 1px solid var(--bdr); border-radius: var(--r-sm); padding: 6px 8px; }
.rs-summary { cursor: pointer; font-size: 12px; color: var(--ink-2); user-select: none; }
.rs-pair { display: flex; gap: 6px; align-items: center; margin-top: 6px; }
.rs-drag { cursor: grab; color: var(--ink-4); padding: 0 2px; user-select: none; }
.rs-drag:hover { color: var(--ink-2); }
.rs-del { color: var(--red); background: none; border: none; cursor: pointer; padding: 0 4px; font-size: 13px; }
.rs-add { margin-top: 8px; }
.rs-body-modes { display: flex; gap: 12px; margin-top: 6px; }
.rs-radio { display: flex; align-items: center; gap: 5px; font-size: 12px; color: var(--ink-2); cursor: pointer; }
</style>
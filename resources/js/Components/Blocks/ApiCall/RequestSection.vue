<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import VarsHint from '../VarsHint.vue';

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

const syncing = ref(false);

watch(() => model.value.path, (newPath) => {
    if (syncing.value) return;
    const qIdx = (newPath ?? '').indexOf('?');
    const qs = qIdx === -1 ? '' : newPath.slice(qIdx + 1);
    syncing.value = true;
    model.value.query = parsePairs(qs);
    syncing.value = false;
}, { flush: 'sync' });

watch(() => model.value.query, () => {
    if (syncing.value) return;
    const pathOnly = (model.value.path ?? '').split('?')[0];
    syncing.value = true;
    model.value.path = pathOnly + buildQueryString(model.value.query);
    syncing.value = false;
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

function addPair(key) {
    model.value[key].push({ key: '', value: '' });
}
function removePair(key, i) {
    model.value[key].splice(i, 1);
}
</script>

<template>
    <div class="space-y-3">
        <div>
            <label class="block text-xs font-medium text-gray-500">Подключение</label>
            <div class="flex gap-2 mt-1">
                <select v-model="model.connection_id" class="flex-1 rounded border-gray-300 text-sm">
                    <option :value="null">— выбрать —</option>
                    <option v-for="c in connections" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
                <a :href="route('bot-connections.index', botId)" target="_blank" class="text-xs text-indigo-600 self-center">+ новое</a>
            </div>
        </div>

        <div class="grid grid-cols-[100px_1fr] gap-2">
            <select v-model="model.method" class="rounded border-gray-300 text-sm">
                <option>GET</option><option>POST</option><option>PUT</option><option>DELETE</option><option>PATCH</option>
            </select>
            <input v-model="model.path" type="text" placeholder="/users/{{state.user_id}}"
                class="rounded border-gray-300 text-sm font-mono" />
        </div>
        <div v-if="fullUrlPreview" class="text-xs text-gray-400 font-mono">→ {{ fullUrlPreview }}</div>
        <VarsHint />

        <details class="border rounded p-2">
            <summary class="cursor-pointer text-xs text-gray-600">Параметры запроса ({{ model.query.length }})</summary>
            <div v-for="(p, i) in model.query" :key="i" class="flex gap-2 mt-2">
                <input v-model="p.key" placeholder="name" class="flex-1 rounded border-gray-300 text-sm" />
                <input v-model="p.value" placeholder="value" class="flex-1 rounded border-gray-300 text-sm" />
                <button type="button" @click="removePair('query', i)" class="text-red-500 px-2">✕</button>
            </div>
            <button type="button" @click="addPair('query')" class="mt-2 text-xs text-indigo-600">+ параметр</button>
        </details>

        <details class="border rounded p-2">
            <summary class="cursor-pointer text-xs text-gray-600">Заголовки ({{ model.headers.length }})</summary>
            <div v-for="(h, i) in model.headers" :key="i" class="flex gap-2 mt-2">
                <input v-model="h.key" placeholder="Header" class="flex-1 rounded border-gray-300 text-sm" />
                <input v-model="h.value" placeholder="Value" class="flex-1 rounded border-gray-300 text-sm" />
                <button type="button" @click="removePair('headers', i)" class="text-red-500 px-2">✕</button>
            </div>
            <button type="button" @click="addPair('headers')" class="mt-2 text-xs text-indigo-600">+ заголовок</button>
        </details>

        <details class="border rounded p-2" :open="model.body_mode !== 'none'">
            <summary class="cursor-pointer text-xs text-gray-600">Тело запроса</summary>
            <div class="mt-2 space-x-3 text-sm">
                <label><input type="radio" v-model="model.body_mode" value="none" /> Нет</label>
                <label><input type="radio" v-model="model.body_mode" value="json" /> JSON</label>
                <label><input type="radio" v-model="model.body_mode" value="form" /> Form data</label>
            </div>
            <textarea v-if="model.body_mode === 'json'" v-model="model.body" rows="6"
                class="mt-2 w-full font-mono text-xs rounded border-gray-300"
                placeholder='{"fields": {"NAME": "{{state.name}}"}}' />
            <div v-else-if="model.body_mode === 'form'">
                <div v-for="(p, i) in (model.body ?? [])" :key="i" class="flex gap-2 mt-2">
                    <input v-model="p.key" class="flex-1 rounded border-gray-300 text-sm" />
                    <input v-model="p.value" class="flex-1 rounded border-gray-300 text-sm" />
                </div>
                <button type="button" @click="(model.body ??= []).push({key:'', value:''})" class="mt-2 text-xs text-indigo-600">+ поле</button>
            </div>
        </details>
    </div>
</template>
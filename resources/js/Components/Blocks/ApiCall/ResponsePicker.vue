<script setup>
import { ref, computed } from 'vue';
import axios from 'axios';
import JsonTree from './JsonTree.vue';
import StateSampleEditor from './StateSampleEditor.vue';
import ResponseMappingList from './ResponseMappingList.vue';

const model = defineModel({ type: Object });
const props = defineProps({ botId: [Number, String] });

const stateSample = ref({});
const result = ref(null);
const loading = ref(false);

const referencedStateKeys = computed(() => {
    const re = /\{\{\s*state\.([a-zA-Z_][a-zA-Z0-9_]*)\s*\}\}/g;
    const seen = new Set();
    const scan = (s) => { if (typeof s !== 'string') return; let m; while ((m = re.exec(s))) seen.add(m[1]); };
    scan(model.value.path);
    (model.value.headers ?? []).forEach(h => { scan(h.key); scan(h.value); });
    (model.value.query ?? []).forEach(q => { scan(q.key); scan(q.value); });
    if (typeof model.value.body === 'string') scan(model.value.body);
    return [...seen];
});

async function run() {
    if (! model.value.connection_id) {
        alert('Сначала выберите подключение');
        return;
    }
    loading.value = true;
    try {
        const { data } = await axios.post(
            route('bot-connections.test', [props.botId, model.value.connection_id]),
            {
                method: model.value.method,
                path: model.value.path,
                headers: model.value.headers,
                query: model.value.query,
                body_mode: model.value.body_mode,
                body: model.value.body,
                state_sample: stateSample.value,
            },
        );
        result.value = data;
    } catch (e) {
        result.value = { error: e.response?.data?.message ?? e.message, status: null };
    } finally {
        loading.value = false;
    }
}

function onPick(path, value) {
    const suggested = path.split('.').pop().replace(/[^a-zA-Z0-9_]+/g, '_').toLowerCase();
    const key = prompt(`Сохранить ${path} в state как:`, suggested);
    if (! key) return;
    if ((model.value.response_mapping ?? []).some(m => m.state_key === key)) {
        alert(`state.${key} уже используется`);
        return;
    }
    model.value.response_mapping = [...(model.value.response_mapping ?? []), { json_path: path, state_key: key }];
}
</script>

<template>
    <div class="rp-wrap">
        <StateSampleEditor v-model="stateSample" :keys="referencedStateKeys" />

        <button type="button" @click="run" :disabled="loading" class="rp-run-btn">
            {{ loading ? 'Выполняется…' : '▶ Выполнить тестовый запрос' }}
        </button>

        <div v-if="result" class="rp-result">
            <div v-if="result.error" class="rp-error">Ошибка: {{ result.error }}</div>
            <div v-else>
                <div class="rp-status">
                    Статус:
                    <span :class="result.status >= 400 ? 'rp-status--err' : 'rp-status--ok'">{{ result.status }}</span>
                    · {{ result.duration_ms }} мс<span v-if="result.truncated"> · обрезано</span>
                </div>
                <div v-if="result.body_json">
                    <JsonTree :value="result.body_json" :mapping="model.response_mapping" @pick="onPick" />
                </div>
                <pre v-else class="rp-raw">{{ result.body_raw }}</pre>
            </div>
        </div>

        <ResponseMappingList v-model="model.response_mapping" />
    </div>
</template>

<style scoped>
.rp-wrap { display: flex; flex-direction: column; gap: 10px; }
.rp-run-btn {
    padding: 4px 12px;
    background: var(--blue);
    color: #fff;
    border: none;
    border-radius: var(--r-sm);
    font-size: 12px;
    font-family: var(--font);
    cursor: pointer;
    transition: background .1s;
    align-self: flex-start;
}
.rp-run-btn:hover { background: var(--blue-d); }
.rp-run-btn:disabled { opacity: .6; cursor: default; }
.rp-result {
    border: 1px solid var(--bdr);
    border-radius: var(--r-sm);
    padding: 8px;
    font-size: 12px;
}
.rp-error { color: var(--red); }
.rp-status { color: var(--ink-3); margin-bottom: 6px; }
.rp-status--ok  { color: var(--green); font-weight: 600; }
.rp-status--err { color: var(--red);   font-weight: 600; }
.rp-raw { white-space: pre-wrap; color: var(--ink-2); font-family: var(--mono); font-size: 11px; }
</style>
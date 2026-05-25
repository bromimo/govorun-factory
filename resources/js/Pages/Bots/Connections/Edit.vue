<script setup>
import axios from 'axios';
import { ref, computed, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BotSidebar from '@/Components/Bots/BotSidebar.vue';
import ErButton from '@/Components/Ui/ErButton.vue';
import ErInput from '@/Components/Ui/ErInput.vue';
import AuthConfigFields from '@/Components/Connections/AuthConfigFields.vue';
import { useToast } from '@/composables/useToast';
import { useDirtyGuard } from '@/composables/useDirtyGuard';

const props = defineProps({
    bot: Object,
    connection: { type: Object, default: null },
    can: { type: Object, default: () => ({ update: false }) },
});

const toast = useToast();

const isEdit = computed(() => !!props.connection);

const form = useForm({
    name: props.connection?.name ?? '',
    slug: props.connection?.slug ?? '',
    base_url: props.connection?.base_url ?? '',
    auth_type: props.connection?.auth_type ?? 'none',
    auth_config: props.connection
        ? { ...(props.connection.auth_config ?? {}), token: '', password: '', value: '' }
        : {},
    default_headers: props.connection?.default_headers ?? [],
});

useDirtyGuard(() => form.isDirty);

const slugManuallyEdited = ref(isEdit.value);

watch(() => form.name, (n) => {
    if (!slugManuallyEdited.value) {
        form.slug = transliterate(n);
    }
});

watch(() => form.auth_type, () => {
    form.auth_config = {};
});

function onSlugInput(val) {
    form.slug = val;
    slugManuallyEdited.value = true;
}

function transliterate(s) {
    const map = {
        а: 'a', б: 'b', в: 'v', г: 'g', д: 'd', е: 'e', ё: 'yo', ж: 'zh', з: 'z',
        и: 'i', й: 'i', к: 'k', л: 'l', м: 'm', н: 'n', о: 'o', п: 'p', р: 'r',
        с: 's', т: 't', у: 'u', ф: 'f', х: 'h', ц: 'c', ч: 'ch', ш: 'sh', щ: 'sch',
        ъ: '', ы: 'y', ь: '', э: 'e', ю: 'yu', я: 'ya',
    };
    return s.toLowerCase()
        .split('')
        .map(c => map[c] ?? c)
        .join('')
        .replace(/[^a-z0-9]+/g, '_')
        .replace(/^_+|_+$/g, '');
}

function save() {
    if (isEdit.value) {
        form.put(route('bot-connections.update', [props.bot.id, props.connection.id]), {
            preserveScroll: true,
            onSuccess: () => toast.success('Подключение обновлено'),
        });
    } else {
        form.post(route('bot-connections.store', props.bot.id));
    }
}

function goBack() {
    router.visit(route('bots.edit', props.bot.id), { data: { tab: 'connections' } });
}

function goToTab(key) {
    router.visit(route('bots.edit', props.bot.id), { data: { tab: key } });
}

function addHeader() {
    form.default_headers.push({ key: '', value: '' });
}

function removeHeader(i) {
    form.default_headers.splice(i, 1);
}

const testing = ref(false);
const testResult = ref(null);

async function runTest() {
    if (!props.connection?.id) {
        return;
    }
    testing.value = true;
    testResult.value = null;
    try {
        const { data } = await axios.post(
            route('bot-connections.test', [props.bot.id, props.connection.id]),
            { method: 'GET', path: '/', body_mode: 'none' },
        );
        const bodyStr = typeof data.body_json === 'object'
            ? JSON.stringify(data.body_json)
            : String(data.body_json ?? '');
        testResult.value = {
            reachable: true,
            status: data.status,
            durationMs: data.durationMs,
            body: bodyStr,
        };
    } catch (err) {
        testResult.value = {
            reachable: false,
            status: null,
            durationMs: 0,
            body: err.response?.data?.message ?? err.message ?? '',
        };
    } finally {
        testing.value = false;
    }
}


const pageTitle = computed(() => isEdit.value
    ? `Подключение: ${props.connection.name}`
    : 'Новое подключение');
</script>

<template>
    <Head :title="pageTitle" />
    <AuthenticatedLayout :title="pageTitle">
        <template #breadcrumbs>
            <Link :href="route('dashboard')">Главная</Link>
            <span class="sep">›</span>
            <Link :href="route('bots.edit', bot.id)">{{ bot.name }}</Link>
            <span class="sep">›</span>
            <Link :href="route('bots.edit', bot.id) + '?tab=connections'">Подключения</Link>
            <span class="sep">›</span>
            <span>{{ isEdit ? connection.name : 'Новое подключение' }}</span>
        </template>

        <template #sidebar>
            <BotSidebar :bot="bot" active-key="connections" :on-tab-change="goToTab" />
        </template>

        <form @submit.prevent="save" class="cp-page">
            <div class="cp-body">
                <div class="cp-field">
                    <label class="cp-lbl">Имя</label>
                    <ErInput v-model="form.name" long :disabled="!can.update" />
                    <p v-if="form.errors.name" class="cp-err">{{ form.errors.name }}</p>
                </div>

                <div class="cp-field">
                    <label class="cp-lbl">Slug</label>
                    <ErInput :modelValue="form.slug" @update:modelValue="onSlugInput" long :disabled="!can.update" />
                    <p class="cp-hint">Используется в <code>config/connections.php</code> экспортируемого бота.</p>
                    <p v-if="form.errors.slug" class="cp-err">{{ form.errors.slug }}</p>
                </div>

                <div class="cp-field">
                    <label class="cp-lbl">Base URL</label>
                    <ErInput v-model="form.base_url" placeholder="https://api.example.com" long :disabled="!can.update" />
                    <p v-if="form.errors.base_url" class="cp-err">{{ form.errors.base_url }}</p>
                </div>

                <div class="cp-field">
                    <label class="cp-lbl">Авторизация</label>
                    <div class="cp-auth-radios">
                        <label v-for="t in ['none', 'api_key', 'bearer', 'basic']" :key="t" class="cp-radio-lbl">
                            <input type="radio" v-model="form.auth_type" :value="t" :disabled="!can.update" />
                            {{ t }}
                        </label>
                    </div>
                    <AuthConfigFields
                        :type="form.auth_type"
                        v-model="form.auth_config"
                        :is-edit="isEdit"
                        :errors="form.errors"
                    />
                </div>

                <details class="cp-headers">
                    <summary class="cp-headers-sum">
                        Заголовки по умолчанию ({{ form.default_headers.length }})
                    </summary>
                    <div v-for="(h, i) in form.default_headers" :key="i" class="cp-hdr-row">
                        <ErInput v-model="h.key" placeholder="Header" :disabled="!can.update" />
                        <ErInput v-model="h.value" placeholder="Value" :disabled="!can.update" />
                        <button class="cp-hdr-rm" type="button" :disabled="!can.update" @click="removeHeader(i)">✕</button>
                    </div>
                    <ErButton type="button" size="sm" :disabled="!can.update" @click="addHeader" style="margin-top:6px">
                        + Добавить заголовок
                    </ErButton>
                </details>
            </div>

            <div v-if="testResult" class="cp-test-result">
                <div class="cp-test-h">
                    <span class="cp-test-badge" :class="testResult.reachable ? 'badge-ok' : 'badge-fail'">
                        {{ testResult.reachable ? 'Сервер доступен' : 'Сервер недоступен' }}
                    </span>
                    <span v-if="testResult.status" class="cp-test-status">HTTP {{ testResult.status }}</span>
                    <span v-if="testResult.durationMs" class="cp-test-dur">{{ testResult.durationMs }} мс</span>
                    <button type="button" class="cp-test-x" @click="testResult = null">×</button>
                </div>
                <details v-if="testResult.body" class="cp-test-details">
                    <summary>Ответ сервера</summary>
                    <pre class="cp-test-body">{{ testResult.body }}</pre>
                </details>
            </div>

            <div class="cp-foot">
                <ErButton
                    type="button"
                    size="sm"
                    :disabled="!connection?.id || testing"
                    :title="!connection?.id ? 'Сначала сохраните подключение' : ''"
                    @click="runTest"
                >
                    {{ testing ? 'Проверка…' : 'Проверить' }}
                </ErButton>
                <span style="flex: 1"></span>
                <ErButton type="button" @click="goBack">Назад</ErButton>
                <ErButton variant="primary" type="submit" :disabled="form.processing || !can.update">
                    {{ isEdit ? 'Сохранить' : 'Создать' }}
                </ErButton>
            </div>
        </form>
    </AuthenticatedLayout>
</template>

<style scoped>
.cp-page {
    display: flex;
    flex-direction: column;
    min-height: 0;
}
.cp-body {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 14px;
    min-height: 0;
}
.cp-foot {
    flex-shrink: 0;
    margin-top: 16px;
    padding: 10px 0 0;
    border-top: 1px solid var(--bdr);
    display: flex;
    align-items: center;
    gap: 8px;
}

.cp-field { display: flex; flex-direction: column; gap: 4px; }
.cp-lbl { font-size: 11px; font-weight: 600; color: var(--ink-2); }
.cp-hint { font-size: 11px; color: var(--ink-3); margin: 0; }
.cp-hint code { font-family: var(--mono); background: var(--surface-3); padding: 1px 3px; border-radius: 2px; }
.cp-err  { font-size: 11px; color: var(--red); margin: 0; }

.cp-auth-radios { display: flex; flex-wrap: wrap; gap: 12px; font-size: 12px; margin-bottom: 4px; }
.cp-radio-lbl { display: flex; align-items: center; gap: 4px; cursor: pointer; }

.cp-headers {
    border: 1px solid var(--bdr);
    border-radius: var(--r-sm);
    padding: 10px 12px;
    background: var(--surface-2);
}
.cp-headers-sum { cursor: pointer; font-size: 12px; font-weight: 500; color: var(--ink-2); }
.cp-hdr-row { display: flex; gap: 6px; margin-top: 8px; align-items: center; }
.cp-hdr-rm {
    background: none;
    border: none;
    color: var(--red);
    cursor: pointer;
    font-size: 14px;
    padding: 0 4px;
}
.cp-hdr-rm:hover { opacity: .7; }
.cp-hdr-rm:disabled { opacity: .3; cursor: not-allowed; }

.cp-test-result {
    margin: 8px 0 0;
    padding: 8px 10px;
    border: 1px solid var(--bdr);
    border-radius: var(--r-md);
    font-size: 11px;
    background: var(--surface-2);
}
.cp-test-h { display: flex; align-items: center; gap: 8px; }
.cp-test-badge {
    display: inline-flex;
    align-items: center;
    padding: 2px 8px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 600;
}
.badge-ok { background: var(--green-soft); color: var(--green); border: 1px solid var(--green); }
.badge-fail { background: var(--red-soft); color: var(--red); border: 1px solid var(--red); }
.cp-test-status { font-family: var(--mono); font-size: 11px; color: var(--ink-2); }
.cp-test-dur { font-size: 11px; color: var(--ink-3); }
.cp-test-x {
    margin-left: auto;
    background: none;
    border: none;
    cursor: pointer;
    font-size: 14px;
    color: var(--ink-3);
    padding: 0 4px;
}
.cp-test-details { margin-top: 6px; }
.cp-test-details summary {
    cursor: pointer;
    font-size: 11px;
    color: var(--ink-3);
    user-select: none;
}
.cp-test-body {
    margin: 6px 0 0;
    padding: 6px 8px;
    background: var(--surface);
    border: 1px solid var(--bdr-l);
    font-family: var(--mono);
    font-size: 10px;
    color: var(--ink-2);
    white-space: pre-wrap;
    word-break: break-all;
    max-height: 140px;
    overflow: auto;
}

.sep { color: var(--ink-4); margin: 0 6px; font-size: 11px; }
</style>
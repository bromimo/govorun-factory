<script setup>
import { ref, computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AuthConfigFields from './AuthConfigFields.vue';
import ErButton from '@/Components/Ui/ErButton.vue';
import ErInput from '@/Components/Ui/ErInput.vue';

const props = defineProps({
    bot: Object,
    connection: { type: Object, default: null },
});
const emit = defineEmits(['close', 'saved']);

const isEdit = computed(() => !! props.connection);

const form = useForm({
    name: props.connection?.name ?? '',
    slug: props.connection?.slug ?? '',
    base_url: props.connection?.base_url ?? '',
    auth_type: props.connection?.auth_type ?? 'none',
    auth_config: props.connection
        ? { ...props.connection.auth_config, token: '', password: '', value: '' }
        : {},
    default_headers: props.connection?.default_headers ?? [],
});

const slugManuallyEdited = ref(isEdit.value);

watch(() => form.name, (n) => {
    if (! slugManuallyEdited.value) {
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

function submit() {
    const url = isEdit.value
        ? route('bot-connections.update', [props.bot.id, props.connection.id])
        : route('bot-connections.store', props.bot.id);
    const method = isEdit.value ? 'put' : 'post';

    form[method](url, {
        preserveScroll: true,
        onSuccess: () => emit('saved'),
    });
}

function addHeader() {
    form.default_headers.push({ key: '', value: '' });
}

function removeHeader(i) {
    form.default_headers.splice(i, 1);
}
</script>

<template>
    <div class="drawer-overlay">
        <div class="drawer-backdrop" @click="emit('close')" />
        <div class="drawer-panel">
            <div class="drawer-h">
                <span>{{ isEdit ? `Подключение: ${connection.name}` : 'Новое подключение' }}</span>
                <button class="drawer-close" @click="emit('close')">✕</button>
            </div>

            <div class="drawer-body">
                <div class="field-row">
                    <label class="field-lbl">Имя</label>
                    <ErInput v-model="form.name" long />
                    <div v-if="form.errors.name" class="field-err">{{ form.errors.name }}</div>
                </div>

                <div class="field-row">
                    <label class="field-lbl">Slug</label>
                    <ErInput
                        :modelValue="form.slug"
                        @update:modelValue="onSlugInput"
                        long
                    />
                    <div class="field-hint">Используется в config/connections.php экспортируемого бота</div>
                    <div v-if="form.errors.slug" class="field-err">{{ form.errors.slug }}</div>
                </div>

                <div class="field-row">
                    <label class="field-lbl">Base URL</label>
                    <ErInput
                        v-model="form.base_url"
                        placeholder="https://api.example.com"
                        long
                    />
                    <div v-if="form.errors.base_url" class="field-err">{{ form.errors.base_url }}</div>
                </div>

                <div class="field-row">
                    <label class="field-lbl">Авторизация</label>
                    <div class="auth-radio-row">
                        <label v-for="t in ['none', 'api_key', 'bearer', 'basic']" :key="t" class="radio-lbl">
                            <input type="radio" v-model="form.auth_type" :value="t" />
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

                <details class="headers-section">
                    <summary class="headers-summary">
                        Заголовки по умолчанию ({{ form.default_headers.length }})
                    </summary>
                    <div v-for="(h, i) in form.default_headers" :key="i" class="header-row">
                        <ErInput v-model="h.key" placeholder="Header" />
                        <ErInput v-model="h.value" placeholder="Value" />
                        <button class="hdr-remove" @click="removeHeader(i)">✕</button>
                    </div>
                    <button class="hdr-add" @click="addHeader">+ Добавить заголовок</button>
                </details>
            </div>

            <div class="drawer-footer">
                <ErButton @click="emit('close')">Отмена</ErButton>
                <ErButton
                    variant="primary"
                    @click="submit"
                    :disabled="form.processing"
                >
                    {{ isEdit ? 'Сохранить' : 'Создать' }}
                </ErButton>
            </div>
        </div>
    </div>
</template>

<style scoped>
.drawer-overlay { position: fixed; inset: 0; z-index: 40; display: flex; }
.drawer-backdrop { flex: 1; background: rgba(0,0,0,.3); }
.drawer-panel { width: 480px; overflow-y: auto; background: var(--surface); display: flex; flex-direction: column; box-shadow: -4px 0 24px rgba(0,0,0,.15); }
.drawer-h {
    padding: 10px 14px;
    font-size: 13px;
    font-weight: 600;
    background: linear-gradient(180deg, #f4f6f8 0%, #e8ecf0 100%);
    border-bottom: 1px solid var(--bdr);
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.drawer-close {
    background: none;
    border: none;
    font-size: 14px;
    color: var(--ink-3);
    cursor: pointer;
    padding: 2px 4px;
    line-height: 1;
}
.drawer-close:hover { color: var(--ink); }
.drawer-body { padding: 16px; display: flex; flex-direction: column; gap: 14px; flex: 1; }
.drawer-footer {
    padding: 10px 14px;
    border-top: 1px solid var(--bdr);
    background: linear-gradient(180deg, #f4f6f8 0%, #e8ecf0 100%);
    display: flex;
    justify-content: flex-end;
    gap: 8px;
}
.field-row { display: flex; flex-direction: column; gap: 4px; }
.field-lbl { font-size: 11px; color: var(--ink-2); font-weight: 500; }
.field-hint { font-size: 11px; color: var(--ink-3); }
.field-err { font-size: 11px; color: var(--red); }
.auth-radio-row { display: flex; flex-wrap: wrap; gap: 12px; font-size: 12px; margin-bottom: 8px; }
.radio-lbl { display: flex; align-items: center; gap: 4px; cursor: pointer; }
.headers-section { border: 1px solid var(--bdr); border-radius: var(--r-sm); padding: 10px 12px; }
.headers-summary { cursor: pointer; font-size: 12px; font-weight: 500; color: var(--ink-2); }
.header-row { display: flex; gap: 6px; margin-top: 8px; align-items: center; }
.hdr-remove { background: none; border: none; color: var(--red); cursor: pointer; font-size: 14px; padding: 0 4px; }
.hdr-remove:hover { opacity: .7; }
.hdr-add { margin-top: 8px; font-size: 12px; color: var(--blue); background: none; border: none; cursor: pointer; padding: 0; }
.hdr-add:hover { text-decoration: underline; }
</style>
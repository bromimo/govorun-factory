<script setup>
import { ref, computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AuthConfigFields from './AuthConfigFields.vue';

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
    auth_config: props.connection ? { ...props.connection.auth_config } : {},
    default_headers: props.connection?.default_headers ?? [],
});

const slugManuallyEdited = ref(isEdit.value);

watch(() => form.name, (n) => {
    if (! slugManuallyEdited.value) {
        form.slug = transliterate(n);
    }
});

function onSlugInput(e) {
    form.slug = e.target.value;
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
    <div class="fixed inset-0 z-40 flex">
        <div class="flex-1 bg-black/30" @click="emit('close')" />
        <div class="w-[480px] overflow-y-auto bg-white p-6 shadow-xl">
            <h2 class="mb-4 text-lg font-semibold">
                {{ isEdit ? `Подключение: ${connection.name}` : 'Новое подключение' }}
            </h2>

            <div class="space-y-4">
                <div>
                    <label class="text-xs text-gray-500">Имя</label>
                    <input v-model="form.name" class="mt-1 w-full rounded border-gray-300" />
                    <div v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</div>
                </div>

                <div>
                    <label class="text-xs text-gray-500">Slug</label>
                    <input
                        :value="form.slug"
                        @input="onSlugInput"
                        class="mt-1 w-full rounded border-gray-300 font-mono text-sm"
                    />
                    <div class="mt-1 text-xs text-gray-400">Используется в config/connections.php экспортируемого бота</div>
                    <div v-if="form.errors.slug" class="mt-1 text-xs text-red-600">{{ form.errors.slug }}</div>
                </div>

                <div>
                    <label class="text-xs text-gray-500">Base URL</label>
                    <input
                        v-model="form.base_url"
                        placeholder="https://api.example.com"
                        class="mt-1 w-full rounded border-gray-300 font-mono text-sm"
                    />
                    <div v-if="form.errors.base_url" class="mt-1 text-xs text-red-600">{{ form.errors.base_url }}</div>
                </div>

                <div>
                    <label class="text-xs text-gray-500">Авторизация</label>
                    <div class="mt-2 flex flex-wrap gap-4 text-sm">
                        <label v-for="t in ['none', 'api_key', 'bearer', 'basic']" :key="t" class="flex items-center gap-1 cursor-pointer">
                            <input type="radio" v-model="form.auth_type" :value="t" />
                            {{ t }}
                        </label>
                    </div>
                    <AuthConfigFields
                        :type="form.auth_type"
                        v-model="form.auth_config"
                        :is-edit="isEdit"
                    />
                </div>

                <details class="rounded border border-gray-200 p-3">
                    <summary class="cursor-pointer text-sm font-medium text-gray-700">
                        Заголовки по умолчанию ({{ form.default_headers.length }})
                    </summary>
                    <div v-for="(h, i) in form.default_headers" :key="i" class="mt-2 flex gap-2">
                        <input v-model="h.key" placeholder="Header" class="flex-1 rounded border-gray-300 text-sm" />
                        <input v-model="h.value" placeholder="Value" class="flex-1 rounded border-gray-300 text-sm" />
                        <button @click="removeHeader(i)" class="px-2 text-red-500 hover:text-red-700">✕</button>
                    </div>
                    <button @click="addHeader" class="mt-2 text-sm text-indigo-600 hover:text-indigo-800">+ Добавить заголовок</button>
                </details>
            </div>

            <div class="mt-6 flex justify-between">
                <button @click="emit('close')" class="text-gray-500 hover:text-gray-700">Отмена</button>
                <button
                    @click="submit"
                    :disabled="form.processing"
                    class="rounded bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-500 disabled:opacity-50"
                >
                    {{ isEdit ? 'Сохранить' : 'Создать' }}
                </button>
            </div>
        </div>
    </div>
</template>
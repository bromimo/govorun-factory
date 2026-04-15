<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { validationRuleDefs, defaultMessages } from '../Blocks/validationRules.js';

const props = defineProps({
    bot: Object,
    can: Object,
});

const fileInput = ref(null);
const importError = ref('');

const existing = props.bot.config?.validation_messages ?? {};

const form = useForm({
    config: {
        ...props.bot.config,
        validation_messages: Object.fromEntries(
            validationRuleDefs.map(r => [r.name, existing[r.name] ?? ''])
        ),
    },
});

function save() {
    const messages = { ...form.config.validation_messages };
    Object.keys(messages).forEach(k => { if (!messages[k]) delete messages[k]; });

    form.transform(data => ({
        config: { ...data.config, validation_messages: Object.keys(messages).length ? messages : undefined },
    })).put(route('bots.update', props.bot.id));
}

function hasParams(ruleName) {
    return validationRuleDefs.find(r => r.name === ruleName)?.params?.length > 0;
}

function exportJson() {
    const overrides = form.config.validation_messages;
    const snapshot = Object.fromEntries(
        Object.keys(defaultMessages).map(k => [k, overrides[k]?.trim() || defaultMessages[k]])
    );
    const blob = new Blob([JSON.stringify(snapshot, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `validation-messages-${props.bot.name}.json`;
    a.click();
    URL.revokeObjectURL(url);
}

function triggerImport() {
    fileInput.value.click();
}

async function onFilePicked(e) {
    importError.value = '';
    const file = e.target.files[0];
    if (!file) return;

    try {
        const text = await file.text();
        const parsed = JSON.parse(text);

        if (typeof parsed !== 'object' || Array.isArray(parsed) || parsed === null) {
            throw new Error('Ожидался JSON-объект');
        }

        for (const [key, value] of Object.entries(parsed)) {
            if (typeof value !== 'string') continue;
            if (!(key in form.config.validation_messages)) continue;
            form.config.validation_messages[key] = value === defaultMessages[key] ? '' : value;
        }
    } catch (err) {
        importError.value = `Ошибка импорта: ${err.message}`;
    } finally {
        e.target.value = '';
    }
}
</script>

<template>
    <form @submit.prevent="save" class="space-y-4">
        <p class="text-sm text-gray-500">
            Настройте тексты сообщений об ошибках валидации для этого бота.
            Пустые поля используют значения по умолчанию.
        </p>

        <div v-for="rule in validationRuleDefs" :key="rule.name" class="space-y-1">
            <label class="block text-sm font-medium text-gray-700">{{ rule.label }}</label>
            <input v-model="form.config.validation_messages[rule.name]" type="text"
                :placeholder="defaultMessages[rule.name]"
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
            <p v-if="hasParams(rule.name)" class="text-xs text-gray-400">
                Используйте {value}, {min}, {max} для подстановки параметров
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2 pt-4">
            <button v-if="can.update" type="submit" :disabled="form.processing"
                class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500 disabled:opacity-50">
                Сохранить
            </button>
            <button type="button" @click="exportJson"
                class="rounded-md bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200">
                Экспорт JSON
            </button>
            <button v-if="can.update" type="button" @click="triggerImport"
                class="rounded-md bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200">
                Импорт JSON
            </button>
            <input ref="fileInput" type="file" accept="application/json,.json" class="hidden" @change="onFilePicked">
            <span v-if="form.recentlySuccessful" class="text-sm text-green-600">Сохранено</span>
        </div>

        <p v-if="importError" class="text-sm text-red-600">{{ importError }}</p>
    </form>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import { validationRuleDefs, defaultMessages } from '../Blocks/validationRules.js';

const props = defineProps({
    bot: Object,
    can: Object,
});

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
                Используйте {0}, {1} для подстановки параметров
            </p>
        </div>

        <div v-if="can.update" class="pt-4">
            <button type="submit" :disabled="form.processing"
                class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500 disabled:opacity-50">
                Сохранить
            </button>
            <span v-if="form.recentlySuccessful" class="ml-3 text-sm text-green-600">Сохранено</span>
        </div>
    </form>
</template>

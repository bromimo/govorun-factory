<script setup>
import { Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    bot: Object,
    can: Object,
});

const drivers = [
    { key: 'telegram', label: 'Telegram' },
    { key: 'vk', label: 'VKontakte' },
];

function buildInitialConfig(source) {
    const initial = {};
    const raw = source ?? {};

    for (const driver of drivers) {
        const entry = raw[driver.key];

        if (entry && typeof entry === 'object') {
            initial[driver.key] = { ...entry };
        } else {
            initial[driver.key] = { enabled: false };
        }
    }

    return initial;
}

const form = useForm({
    messenger_config: buildInitialConfig(props.bot.messenger_config),
});

function isEnabled(driverKey) {
    return form.messenger_config[driverKey]?.enabled === true;
}

function toggleDriver(driverKey) {
    const next = { ...form.messenger_config };
    next[driverKey] = { ...(next[driverKey] ?? {}), enabled: ! isEnabled(driverKey) };
    form.messenger_config = next;
}

function save() {
    form.put(route('bots.update', props.bot.id), { preserveScroll: true });
}
</script>

<template>
    <form @submit.prevent="save" class="space-y-6">
        <div v-for="driver in drivers" :key="driver.key"
            class="rounded-lg border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <h4 class="font-medium text-gray-900">{{ driver.label }}</h4>
                <div class="flex items-center gap-3">
                    <Link v-if="driver.key === 'telegram' && isEnabled('telegram') && can.update"
                          :href="route('bots.telegram.profile.edit', bot.id)"
                          class="text-sm text-indigo-600 hover:text-indigo-500">
                        Настройки профиля
                    </Link>
                    <button type="button" @click="toggleDriver(driver.key)" class="text-sm"
                        :class="isEnabled(driver.key) ? 'text-red-600' : 'text-indigo-600'">
                        {{ isEnabled(driver.key) ? 'Отключить' : 'Подключить' }}
                    </button>
                </div>
            </div>

            <p v-if="isEnabled(driver.key)" class="mt-2 text-sm text-gray-500">
                Подключён. Токены и секреты указываются в <code>.env</code> при развёртывании.
            </p>
        </div>

        <div v-if="can.update" class="pt-4">
            <button type="submit" :disabled="form.processing"
                class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500 disabled:opacity-50">
                Сохранить мессенджеры
            </button>
            <span v-if="form.recentlySuccessful" class="ml-3 text-sm text-green-600">Сохранено</span>
        </div>
    </form>

</template>

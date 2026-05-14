<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import TelegramProfileModal from '@/Components/Bots/TelegramProfileModal.vue';

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

const showProfileModal = ref(false);

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
                    <button v-if="driver.key === 'telegram' && isEnabled('telegram') && can.update"
                        type="button" @click="showProfileModal = true"
                        class="text-sm text-indigo-600 hover:text-indigo-500">
                        Настройки профиля
                    </button>
                    <button type="button" @click="toggleDriver(driver.key)" class="text-sm"
                        :class="isEnabled(driver.key) ? 'text-red-600' : 'text-indigo-600'">
                        {{ isEnabled(driver.key) ? 'Отключить' : 'Подключить' }}
                    </button>
                </div>
            </div>

            <template v-if="isEnabled(driver.key)">
                <div v-if="driver.key === 'telegram'" class="mt-3">
                    <label class="block text-xs font-medium text-gray-500">Юзернейм бота</label>
                    <div class="mt-1 flex items-center">
                        <span class="rounded-l-md border border-r-0 border-gray-300 bg-gray-50 px-2 py-1.5 text-sm text-gray-500">@</span>
                        <input
                            v-model="form.messenger_config.telegram.username"
                            type="text"
                            placeholder="mybotname_bot"
                            class="block w-full rounded-r-md border-gray-300 text-sm"
                            :disabled="!can.update"
                        />
                    </div>
                </div>
                <p class="mt-2 text-sm text-gray-500">
                    Подключён. Токены и секреты указываются в <code>.env</code> при развёртывании.
                </p>
            </template>
        </div>

        <div v-if="can.update" class="pt-4">
            <button type="submit" :disabled="form.processing"
                class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500 disabled:opacity-50">
                Сохранить мессенджеры
            </button>
            <span v-if="form.recentlySuccessful" class="ml-3 text-sm text-green-600">Сохранено</span>
        </div>
    </form>

    <TelegramProfileModal :show="showProfileModal" :bot="bot" :can="can"
        @close="showProfileModal = false" />
</template>

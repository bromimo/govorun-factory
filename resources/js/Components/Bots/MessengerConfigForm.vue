<script setup>
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    bot: Object,
    can: Object,
});

const drivers = [
    { key: 'telegram', label: 'Telegram', fields: ['token'] },
    { key: 'vk', label: 'VKontakte', fields: ['token', 'secret', 'confirmation'] },
];

const form = useForm({
    messenger_config: JSON.parse(JSON.stringify(props.bot.messenger_config ?? {})),
});

function toggleDriver(driverKey) {
    if (form.messenger_config[driverKey]) {
        delete form.messenger_config[driverKey];
    } else {
        form.messenger_config[driverKey] = {};
    }
}

function save() {
    form.put(route('bots.update', props.bot.id));
}
</script>

<template>
    <form @submit.prevent="save" class="space-y-6">
        <div v-for="driver in drivers" :key="driver.key"
            class="rounded-lg border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <h4 class="font-medium text-gray-900">{{ driver.label }}</h4>
                <button type="button" @click="toggleDriver(driver.key)" class="text-sm"
                    :class="form.messenger_config[driver.key] ? 'text-red-600' : 'text-indigo-600'">
                    {{ form.messenger_config[driver.key] ? 'Отключить' : 'Подключить' }}
                </button>
            </div>

            <div v-if="form.messenger_config[driver.key]" class="mt-4 space-y-3">
                <div v-for="field in driver.fields" :key="field">
                    <label class="block text-sm font-medium text-gray-700 capitalize">{{ field }}</label>
                    <input v-model="form.messenger_config[driver.key][field]" type="text"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        :placeholder="`Введите ${field}`" />
                </div>
            </div>
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

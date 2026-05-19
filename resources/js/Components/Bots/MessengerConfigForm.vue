<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import TelegramProfileModal from '@/Components/Bots/TelegramProfileModal.vue';
import ErButton from '@/Components/Ui/ErButton.vue';
import ErInput from '@/Components/Ui/ErInput.vue';
import ErFormSection from '@/Components/Ui/ErFormSection.vue';

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
    <form @submit.prevent="save" class="space-y-2">
        <ErFormSection v-for="driver in drivers" :key="driver.key" :title="driver.label">
            <div class="flex items-center gap-2 mb-2">
                <ErButton v-if="driver.key === 'telegram' && isEnabled('telegram') && can.update"
                    type="button" @click="showProfileModal = true">
                    Настройки профиля
                </ErButton>
                <ErButton type="button" @click="toggleDriver(driver.key)"
                    :variant="isEnabled(driver.key) ? 'danger' : 'default'">
                    {{ isEnabled(driver.key) ? 'Отключить' : 'Подключить' }}
                </ErButton>
            </div>

            <template v-if="isEnabled(driver.key)">
                <div v-if="driver.key === 'telegram'" class="flex items-center gap-0 mb-2">
                    <span class="er-prefix">@</span>
                    <ErInput
                        v-model="form.messenger_config.telegram.username"
                        placeholder="mybotname_bot"
                        :disabled="!can.update"
                        :long="true"
                        class="er-inp-no-left-radius"
                    />
                </div>
                <p class="text-xs text-gray-500">
                    Подключён. Токены и секреты указываются в <code>.env</code> при развёртывании.
                </p>
            </template>
        </ErFormSection>

        <div v-if="can.update" class="flex items-center gap-3 pt-2">
            <ErButton type="submit" variant="primary" :disabled="form.processing">
                Сохранить мессенджеры
            </ErButton>
            <span v-if="form.recentlySuccessful" class="text-xs text-green-600">Сохранено</span>
        </div>
    </form>

    <TelegramProfileModal :show="showProfileModal" :bot="bot" :can="can"
        @close="showProfileModal = false" />
</template>

<style scoped>
.er-prefix {
    display: inline-flex;
    align-items: center;
    padding: 0 8px;
    height: 26px;
    border: 1px solid var(--bdr-d);
    border-right: none;
    border-radius: var(--r-sm) 0 0 var(--r-sm);
    background: var(--surface-2);
    font-size: 12px;
    color: var(--ink-3);
    white-space: nowrap;
}
.er-inp-no-left-radius :deep(.er-inp) {
    border-radius: 0 var(--r-sm) var(--r-sm) 0;
}
</style>

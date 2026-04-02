<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import SettingsForm from '@/Components/Bots/SettingsForm.vue';
import MessengerConfigForm from '@/Components/Bots/MessengerConfigForm.vue';

const props = defineProps({
    bot: Object,
    can: Object,
});

const tabs = [
    { key: 'settings', label: 'Настройки' },
    { key: 'routes', label: 'Маршруты' },
    { key: 'flows', label: 'Flow-диалоги' },
    { key: 'messengers', label: 'Мессенджеры' },
];

const activeTab = ref('settings');

function deleteBot() {
    if (confirm('Удалить бота? Это действие необратимо.')) {
        router.delete(route('bots.destroy', props.bot.id));
    }
}
</script>

<template>
    <Head :title="bot.name" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ bot.name }}</h2>
                <div class="flex gap-2">
                    <button v-if="can.export" disabled
                        class="cursor-not-allowed rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white opacity-50">
                        Экспорт ZIP
                    </button>
                    <button v-if="can.delete" @click="deleteBot"
                        class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-500">
                        Удалить
                    </button>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="flex gap-8">
                    <nav class="w-48 shrink-0 space-y-1">
                        <button v-for="tab in tabs" :key="tab.key" @click="activeTab = tab.key"
                            class="block w-full rounded-md px-3 py-2 text-left text-sm font-medium"
                            :class="activeTab === tab.key
                                ? 'bg-indigo-50 text-indigo-700'
                                : 'text-gray-600 hover:bg-gray-50'">
                            {{ tab.label }}
                            <span v-if="tab.key === 'routes'" class="ml-1 text-xs text-gray-400">
                                ({{ bot.routes?.length ?? 0 }})
                            </span>
                            <span v-if="tab.key === 'flows'" class="ml-1 text-xs text-gray-400">
                                ({{ bot.flows?.length ?? 0 }})
                            </span>
                        </button>
                    </nav>

                    <div class="flex-1 rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-900/5">
                        <SettingsForm v-if="activeTab === 'settings'" :bot="bot" :can="can" />

                        <div v-else-if="activeTab === 'routes'" class="text-sm text-gray-500">
                            <p v-if="!bot.routes?.length">Нет маршрутов.</p>
                            <ul v-else class="divide-y divide-gray-100">
                                <li v-for="r in bot.routes" :key="r.id" class="py-3 flex items-center gap-2">
                                    <span class="inline-flex rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800">
                                        {{ r.type }}
                                    </span>
                                    <span v-if="r.match" class="text-gray-600">{{ r.match }}</span>
                                    <span class="ml-auto text-xs text-gray-400">{{ r.handler_type }}</span>
                                </li>
                            </ul>
                        </div>

                        <div v-else-if="activeTab === 'flows'" class="text-sm text-gray-500">
                            <p v-if="!bot.flows?.length">Нет диалогов.</p>
                            <ul v-else class="divide-y divide-gray-100">
                                <li v-for="f in bot.flows" :key="f.id" class="py-3">
                                    <span class="font-medium text-gray-900">{{ f.name }}</span>
                                </li>
                            </ul>
                        </div>

                        <MessengerConfigForm v-else-if="activeTab === 'messengers'" :bot="bot" :can="can" />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

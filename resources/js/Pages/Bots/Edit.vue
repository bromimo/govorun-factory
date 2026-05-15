<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import Modal from '@/Components/Modal.vue';
import SettingsForm from '@/Components/Bots/SettingsForm.vue';
import MessengerConfigForm from '@/Components/Bots/MessengerConfigForm.vue';
import RouteList from '@/Components/Routes/RouteList.vue';
import FlowList from '@/Components/Flows/FlowList.vue';
import ValidationMessagesForm from '@/Components/Bots/ValidationMessagesForm.vue';
import MediaLibrary from '@/Components/Bots/MediaLibrary.vue';

const props = defineProps({
    bot: Object,
    can: Object,
});

const tabs = [
    { key: 'settings', label: 'Настройки' },
    { key: 'routes', label: 'Маршруты' },
    { key: 'flows', label: 'Flow-диалоги' },
    { key: 'validation', label: 'Валидация' },
    { key: 'messengers', label: 'Мессенджеры' },
    { key: 'media', label: 'Медиатека' },
];

const activeTab = ref('settings');
const exportErrors = ref([]);

function exportBot() {
    exportErrors.value = [];

    fetch(route('bots.export', props.bot.id), {
        headers: {
            'Accept': 'application/json',
        },
    }).then(async (response) => {
        if (response.ok) {
            const blob = await response.blob();
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = response.headers.get('content-disposition')?.split('filename=')[1]?.replace(/"/g, '') || 'bot.zip';
            a.click();
            URL.revokeObjectURL(url);
        } else {
            const data = await response.json();
            exportErrors.value = data.errors || ['Ошибка экспорта'];
        }
    });
}

const confirmingDeletion = ref(false);

function deleteBot() {
    router.delete(route('bots.destroy', props.bot.id));
}
</script>

<template>
    <Head :title="bot.name" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ bot.name }}</h2>
                <div class="flex gap-2">
                    <button v-if="can.export" @click="exportBot"
                        class="rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-500">
                        Экспорт ZIP
                    </button>
                    <button v-if="can.delete" @click="confirmingDeletion = true"
                        class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-500">
                        Удалить
                    </button>
                </div>
            </div>
        </template>

        <div v-if="exportErrors.length" class="mx-auto mt-2 max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-md bg-red-50 p-3">
                <ul class="text-sm text-red-700">
                    <li v-for="(err, i) in exportErrors" :key="i">{{ err }}</li>
                </ul>
            </div>
        </div>

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

                        <RouteList v-else-if="activeTab === 'routes'"
                            :bot-id="bot.id" :routes="bot.routes ?? []" :flows="bot.flows ?? []"
                            :can-update="can.update" />

                        <FlowList v-else-if="activeTab === 'flows'"
                            :bot-id="bot.id" :flows="bot.flows ?? []"
                            :can-update="can.update" />

                        <ValidationMessagesForm v-else-if="activeTab === 'validation'" :bot="bot" :can="can" />

                        <MessengerConfigForm v-else-if="activeTab === 'messengers'" :bot="bot" :can="can" />

                        <MediaLibrary v-else-if="activeTab === 'media'" :bot="bot" />
                    </div>
                </div>
            </div>
        </div>

        <Modal :show="confirmingDeletion" max-width="md" @close="confirmingDeletion = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">
                    Удалить бота «{{ bot.name }}»?
                </h2>
                <p class="mt-2 text-sm text-gray-600">
                    Будут удалены все маршруты, flow-диалоги и настройки. Действие необратимо.
                </p>
                <div class="mt-6 flex justify-end gap-3">
                    <button @click="confirmingDeletion = false"
                        class="rounded-md bg-white px-4 py-2 text-sm font-medium text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50">
                        Отмена
                    </button>
                    <button @click="deleteBot"
                        class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-500">
                        Удалить
                    </button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>

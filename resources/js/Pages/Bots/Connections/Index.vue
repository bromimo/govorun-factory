<script setup>
import { ref } from 'vue';
import { router, Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ConnectionDrawer from '@/Components/Connections/ConnectionDrawer.vue';

const props = defineProps({
    bot: Object,
    connections: Array,
});

const drawerOpen = ref(false);
const editingConnection = ref(null);

function openCreate() {
    editingConnection.value = null;
    drawerOpen.value = true;
}

function openEdit(connection) {
    editingConnection.value = connection;
    drawerOpen.value = true;
}

function onSaved() {
    drawerOpen.value = false;
    router.reload({ only: ['connections'] });
}

function destroy(connection) {
    if (! confirm(`Удалить подключение «${connection.name}»?`)) {
        return;
    }
    router.delete(route('bot-connections.destroy', [props.bot.id, connection.id]), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head :title="`Подключения · ${bot.name}`" />
    <AuthenticatedLayout>
        <div class="mx-auto max-w-4xl px-4 py-6">
            <div class="mb-4 flex items-center gap-3">
                <Link :href="route('bots.edit', bot.id)" class="text-sm text-gray-500 hover:text-gray-700">
                    ← Назад к боту
                </Link>
                <h1 class="text-xl font-semibold">Подключения «{{ bot.name }}»</h1>
            </div>

            <div class="mb-4 flex justify-end">
                <button @click="openCreate" class="rounded bg-indigo-600 px-3 py-2 text-sm text-white hover:bg-indigo-500">
                    + Добавить
                </button>
            </div>

            <div class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-900/5">
                <table class="w-full text-sm">
                    <thead class="border-b border-gray-200 bg-gray-50 text-left text-xs text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Имя</th>
                            <th class="px-4 py-3">Slug</th>
                            <th class="px-4 py-3">Base URL</th>
                            <th class="px-4 py-3">Авторизация</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="c in connections" :key="c.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium">{{ c.name }}</td>
                            <td class="px-4 py-3 font-mono text-xs text-gray-500">{{ c.slug }}</td>
                            <td class="px-4 py-3 font-mono text-xs">{{ c.base_url }}</td>
                            <td class="px-4 py-3">
                                {{ c.auth_type }}
                                <span v-if="c.auth_config_preview" class="ml-2 font-mono text-xs text-gray-400">{{ c.auth_config_preview }}</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button @click="openEdit(c)" class="mr-3 text-indigo-600 hover:text-indigo-800">✎</button>
                                <button @click="destroy(c)" class="text-red-600 hover:text-red-800">🗑</button>
                            </td>
                        </tr>
                        <tr v-if="connections.length === 0">
                            <td colspan="5" class="px-4 py-8 text-center text-gray-400">Пока нет подключений</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <ConnectionDrawer
                v-if="drawerOpen"
                :bot="bot"
                :connection="editingConnection"
                @close="drawerOpen = false"
                @saved="onSaved"
            />
        </div>
    </AuthenticatedLayout>
</template>

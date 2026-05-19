<script setup>
import { ref } from 'vue';
import { router, Head, Link } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ConnectionDrawer from '@/Components/Connections/ConnectionDrawer.vue';
import ErTable from '@/Components/Ui/ErTable.vue';
import ErButton from '@/Components/Ui/ErButton.vue';
import ErBadge from '@/Components/Ui/ErBadge.vue';

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
    <AuthenticatedLayout title="Подключения" :subtitle="bot.name">
        <div class="page-wrap">
            <div class="back-row">
                <Link :href="route('bots.edit', bot.id)" class="back-link">← Назад к боту</Link>
            </div>

            <ErTable>
                <template #toolbar>
                    <ErButton variant="primary" size="sm" @click="openCreate">
                        <Plus :size="12" />Добавить подключение
                    </ErButton>
                </template>
                <template #thead>
                    <tr>
                        <th>Название</th>
                        <th>Slug</th>
                        <th>Base URL</th>
                        <th>Тип авторизации</th>
                        <th style="width: 80px;"></th>
                    </tr>
                </template>
                <tr v-if="connections.length === 0">
                    <td colspan="5" style="text-align: center; color: var(--ink-3); padding: 24px;">Нет подключений</td>
                </tr>
                <tr v-for="c in connections" :key="c.id">
                    <td>{{ c.name }}</td>
                    <td class="tbl-mono">{{ c.slug }}</td>
                    <td class="tbl-mono">{{ c.base_url }}</td>
                    <td>
                        <ErBadge color="nt">{{ c.auth_type }}</ErBadge>
                        <span v-if="c.auth_config_preview" class="auth-preview">{{ c.auth_config_preview }}</span>
                    </td>
                    <td>
                        <div style="display: flex; gap: 4px;">
                            <button class="tbl-act-btn" @click="openEdit(c)">Изменить</button>
                            <button class="tbl-act-btn tbl-act-btn--danger" @click="destroy(c)">Удалить</button>
                        </div>
                    </td>
                </tr>
            </ErTable>
        </div>

        <ConnectionDrawer
            v-if="drawerOpen"
            :bot="bot"
            :connection="editingConnection"
            @close="drawerOpen = false"
            @saved="onSaved"
        />
    </AuthenticatedLayout>
</template>

<style scoped>
.page-wrap { padding: 16px 20px; }
.back-row { margin-bottom: 12px; }
.back-link { font-size: 12px; color: var(--ink-3); text-decoration: none; }
.back-link:hover { color: var(--ink); }
.tbl-mono { font-family: var(--mono); font-size: 11px; color: var(--ink-2); }
.auth-preview { font-family: var(--mono); font-size: 11px; color: var(--ink-3); margin-left: 6px; }
.tbl-act-btn {
    font-size: 11px;
    color: var(--blue);
    text-decoration: none;
    padding: 1px 6px;
    border: 1px solid var(--bdr-l);
    border-radius: 2px;
    background: var(--surface);
    cursor: pointer;
}
.tbl-act-btn:hover { background: var(--blue-soft); }
.tbl-act-btn--danger { color: var(--red); }
.tbl-act-btn--danger:hover { background: var(--red-soft); }
</style>
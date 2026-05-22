<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import ErTable from '@/Components/Ui/ErTable.vue';
import ErButton from '@/Components/Ui/ErButton.vue';
import ErBadge from '@/Components/Ui/ErBadge.vue';
import ConfirmModal from '@/Components/Ui/ConfirmModal.vue';
import ConnectionDrawer from '@/Components/Connections/ConnectionDrawer.vue';

const props = defineProps({
    bot: Object,
    connections: Array,
});

const drawerOpen = ref(false);
const editingConnection = ref(null);
const confirmConn = ref(null);

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
}

function doDestroy() {
    router.delete(route('bot-connections.destroy', [props.bot.id, confirmConn.value.id]), {
        preserveScroll: true,
    });
    confirmConn.value = null;
}
</script>

<template>
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
        <tr v-for="c in connections" :key="c.id" @dblclick="openEdit(c)" style="cursor: pointer;">
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
                    <button class="tbl-act-btn tbl-act-btn--danger" @click="confirmConn = c">Удалить</button>
                </div>
            </td>
        </tr>
    </ErTable>

    <ConnectionDrawer
        v-if="drawerOpen"
        :bot="bot"
        :connection="editingConnection"
        @close="drawerOpen = false"
        @saved="onSaved"
    />

    <ConfirmModal
        :show="!!confirmConn"
        title="Удалить подключение?"
        :message="`Подключение «${confirmConn?.name}» будет удалено безвозвратно.`"
        confirm-label="Удалить"
        @confirm="doDestroy"
        @cancel="confirmConn = null"
    />
</template>

<style scoped>
.tbl-mono { font-family: var(--mono); font-size: 11px; color: var(--ink-2); }
.auth-preview { font-family: var(--mono); font-size: 11px; color: var(--ink-3); margin-left: 6px; }
</style>
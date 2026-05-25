<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import ErTable from '@/Components/Ui/ErTable.vue';
import ErButton from '@/Components/Ui/ErButton.vue';
import ErBadge from '@/Components/Ui/ErBadge.vue';
import ConfirmModal from '@/Components/Ui/ConfirmModal.vue';

const props = defineProps({
    bot: Object,
    connections: Array,
});

const confirmConn = ref(null);

function openEdit(connection) {
    router.visit(route('bot-connections.edit', [props.bot.id, connection.id]));
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
            <Link :href="route('bot-connections.create', bot.id)">
                <ErButton variant="primary" size="sm">
                    <Plus :size="12" />Добавить подключение
                </ErButton>
            </Link>
        </template>
        <template #thead>
            <tr>
                <th style="width: 28px;"><input type="checkbox" /></th>
                <th style="width: 48px;">ID</th>
                <th>Название</th>
                <th>Slug</th>
                <th>Base URL</th>
                <th>Тип авторизации</th>
                <th style="width: 80px;"></th>
            </tr>
        </template>
        <tr v-if="connections.length === 0">
            <td colspan="7" style="text-align: center; color: var(--ink-3); padding: 24px;">Нет подключений</td>
        </tr>
        <tr v-for="c in connections" :key="c.id" @dblclick="openEdit(c)" style="cursor: pointer;">
            <td><input type="checkbox" /></td>
            <td class="tbl-mono">{{ c.id }}</td>
            <td>{{ c.name }}</td>
            <td class="tbl-mono">{{ c.slug }}</td>
            <td class="tbl-mono">{{ c.base_url }}</td>
            <td>
                <ErBadge color="nt">{{ c.auth_type }}</ErBadge>
                <span v-if="c.auth_config_preview" class="auth-preview">{{ c.auth_config_preview }}</span>
            </td>
            <td>
                <div class="tbl-acts">
                    <Link :href="route('bot-connections.edit', [bot.id, c.id])" class="tbl-act-btn">Изменить</Link>
                    <button class="tbl-act-btn tbl-act-btn--danger" @click="confirmConn = c">Удалить</button>
                </div>
            </td>
        </tr>
    </ErTable>

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
.tbl-acts { display: flex; gap: 2px; }
</style>
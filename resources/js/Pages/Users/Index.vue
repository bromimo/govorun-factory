<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import ErButton from '@/Components/Ui/ErButton.vue'
import ErTable from '@/Components/Ui/ErTable.vue'
import ErBadge from '@/Components/Ui/ErBadge.vue'
import ErCounterCard from '@/Components/Ui/ErCounterCard.vue'
import { Head, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { Users as UsersIcon, Shield, UserCheck, RefreshCw, Plus } from 'lucide-vue-next'

const props = defineProps({
    users: Array,
    can: Object,
})

const search = ref('')

function filtered() {
    if (!search.value) {
        return props.users
    }
    const q = search.value.toLowerCase()
    return props.users.filter(u => u.name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q))
}

function roleBadgeColor(role) {
    return { admin: 'rd', editor: 'bl', viewer: 'nt' }[role] ?? 'nt'
}
</script>

<template>
    <Head title="Пользователи" />
    <AuthenticatedLayout title="Пользователи">
        <template #subbar>
            <button class="er-tab act">Список</button>
            <button class="er-tab">Роли</button>
        </template>

        <template #actions>
            <ErButton v-if="can?.create" variant="primary" :as="'a'" :href="route('users.create')">
                <Plus :size="13" />Новый пользователь
            </ErButton>
        </template>

        <!-- Counter cards -->
        <div class="counter-row" style="margin-bottom: 12px;">
            <ErCounterCard label="Всего" :value="users.length" color="blue" :icon="UsersIcon" />
            <ErCounterCard label="Администраторов" :value="users.filter(u => u.role === 'admin').length" color="red" :icon="Shield" />
            <ErCounterCard label="Редакторов" :value="users.filter(u => u.role === 'editor').length" color="orange" :icon="UserCheck" />
            <ErCounterCard label="Наблюдателей" :value="users.filter(u => u.role === 'viewer').length" color="green" :icon="UserCheck" />
        </div>

        <!-- Search -->
        <div style="margin-bottom: 10px; display: flex; gap: 6px;">
            <input v-model="search" type="text" class="er-inp" placeholder="Поиск по имени или email..." style="max-width: 320px;" />
            <ErButton size="sm" @click="router.reload()"><RefreshCw :size="12" /></ErButton>
        </div>

        <ErTable>
            <template #thead>
                <tr>
                    <th>Имя</th>
                    <th>Email</th>
                    <th>Роль</th>
                    <th>Создан</th>
                    <th style="width: 80px;"></th>
                </tr>
            </template>

            <tr v-if="filtered().length === 0">
                <td colspan="5" class="tbl-empty">Пользователи не найдены</td>
            </tr>
            <tr v-for="user in filtered()" :key="user.id" class="tbl-row"
                @dblclick="router.visit(route('users.edit', user.id))">
                <td class="tbl-name">{{ user.name }}</td>
                <td class="tbl-mono">{{ user.email }}</td>
                <td>
                    <ErBadge :color="roleBadgeColor(user.role)">{{ user.role }}</ErBadge>
                </td>
                <td class="tbl-mono">{{ new Date(user.created_at).toLocaleDateString('ru') }}</td>
                <td>
                    <div class="tbl-acts">
                        <a :href="route('users.edit', user.id)" class="tbl-act-btn">Изменить</a>
                    </div>
                </td>
            </tr>

            <template #paging>
                <span>Записи 1—{{ filtered().length }} из {{ users.length }}</span>
            </template>
        </ErTable>
    </AuthenticatedLayout>
</template>

<style scoped>
.er-tab { padding: 0 14px; display: flex; align-items: center; color: var(--ink-2); cursor: pointer; font-weight: 500; border-bottom: 2px solid transparent; margin-bottom: -1px; font-size: 12px; background: none; border-top: none; border-left: none; border-right: none; font-family: var(--font); transition: color .1s; }
.er-tab.act { color: var(--blue-d); border-bottom-color: var(--blue); font-weight: 600; }
.counter-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; }
.er-inp { height: 26px; padding: 0 8px; border: 1px solid var(--bdr-d); border-radius: var(--r-sm); background: #fff; color: var(--ink); font-size: 12px; font-family: var(--font); width: 100%; box-shadow: inset 0 1px 1px rgba(0,0,0,.06); }
.er-inp:focus { outline: none; border-color: var(--blue); box-shadow: 0 0 0 2px rgba(58,114,196,.2); }
.tbl-row { cursor: pointer; }
.tbl-name { font-weight: 500; color: var(--ink); }
.tbl-mono { font-family: var(--mono); font-size: 11px; color: var(--ink-2); }
.tbl-empty { text-align: center; color: var(--ink-3); padding: 24px; }
.tbl-acts { display: flex; gap: 4px; }
</style>
<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import ErButton from '@/Components/Ui/ErButton.vue'
import ErTable from '@/Components/Ui/ErTable.vue'
import ErBadge from '@/Components/Ui/ErBadge.vue'
import ErCounterCard from '@/Components/Ui/ErCounterCard.vue'
import { Head, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import { Users as UsersIcon, Shield, UserCheck, RefreshCw, Plus } from 'lucide-vue-next'

const props = defineProps({
    users: Array,
})

const activeTab = ref('list')
const search = ref('')

const filtered = computed(() => {
    if (!search.value) return props.users
    const q = search.value.toLowerCase()
    return props.users.filter(u => u.name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q))
})

function roleBadgeColor(role) {
    return { admin: 'rd', editor: 'bl', viewer: 'nt' }[role] ?? 'nt'
}

const roles = [
    {
        value: 'admin',
        label: 'Администратор',
        color: 'rd',
        description: 'Полный доступ ко всем разделам системы.',
        permissions: [
            'Просмотр, создание, редактирование и удаление любых ботов',
            'Экспорт любых ботов',
            'Управление пользователями и ролями',
            'Управление плагинами',
        ],
    },
    {
        value: 'editor',
        label: 'Редактор',
        color: 'bl',
        description: 'Работает только со своими ботами.',
        permissions: [
            'Просмотр всех ботов',
            'Создание, редактирование и экспорт своих ботов',
            'Нет доступа к удалению ботов других пользователей',
            'Нет доступа к управлению пользователями и плагинами',
        ],
    },
    {
        value: 'viewer',
        label: 'Наблюдатель',
        color: 'nt',
        description: 'Только просмотр без возможности вносить изменения.',
        permissions: [
            'Просмотр всех ботов',
            'Нет доступа к созданию, редактированию и удалению',
            'Нет доступа к экспорту',
            'Нет доступа к управлению пользователями и плагинами',
        ],
    },
]
</script>

<template>
    <Head title="Пользователи" />
    <AuthenticatedLayout title="Пользователи">
        <template #subbar>
            <button :class="['er-tab', activeTab === 'list' ? 'act' : '']" @click="activeTab = 'list'">Список</button>
            <button :class="['er-tab', activeTab === 'roles' ? 'act' : '']" @click="activeTab = 'roles'">Роли</button>
        </template>

        <template #actions>
            <ErButton v-if="activeTab === 'list'" variant="primary" :as="'a'" :href="route('users.create')">
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

        <!-- Вкладка: Список -->
        <template v-if="activeTab === 'list'">
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

                <tr v-if="filtered.length === 0">
                    <td colspan="5" class="tbl-empty">Пользователи не найдены</td>
                </tr>
                <tr v-for="user in filtered" :key="user.id" class="tbl-row"
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
                    <span>Записи 1—{{ filtered.length }} из {{ users.length }}</span>
                </template>
            </ErTable>
        </template>

        <!-- Вкладка: Роли -->
        <div v-else class="roles-grid">
            <div v-for="role in roles" :key="role.value" class="role-card">
                <div class="role-card-header">
                    <ErBadge :color="role.color">{{ role.value }}</ErBadge>
                    <span class="role-label">{{ role.label }}</span>
                    <span class="role-count">{{ users.filter(u => u.role === role.value).length }} польз.</span>
                </div>
                <p class="role-desc">{{ role.description }}</p>
                <ul class="role-perms">
                    <li v-for="p in role.permissions" :key="p">{{ p }}</li>
                </ul>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.er-tab {
    align-self: flex-end;
    padding: 0 14px;
    height: 25px;
    display: flex;
    align-items: center;
    font-size: 12px;
    font-weight: 500;
    font-family: var(--font);
    color: var(--ink-3);
    background: linear-gradient(180deg, #e8ecf0 0%, #dde1e6 100%);
    border: 1px solid var(--bdr-d);
    border-bottom: none;
    border-radius: 4px 4px 0 0;
    cursor: pointer;
    margin-right: 3px;
    margin-bottom: -1px;
    transition: color .1s, background .1s;
    position: relative;
}
.er-tab:hover:not(.act) { background: linear-gradient(180deg, #dde1e6 0%, #d0d5db 100%); color: var(--ink-2); }
.er-tab.act {
    background: #f0f3f5;
    border-color: var(--bdr);
    color: var(--ink);
    font-weight: 600;
    z-index: 1;
}
.counter-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; }
.er-inp { height: 26px; padding: 0 8px; border: 1px solid var(--bdr-d); border-radius: var(--r-sm); background: #fff; color: var(--ink); font-size: 12px; font-family: var(--font); width: 100%; box-shadow: inset 0 1px 1px rgba(0,0,0,.06); }
.er-inp:focus { outline: none; border-color: var(--blue); box-shadow: 0 0 0 2px rgba(58,114,196,.2); }
.tbl-row { cursor: pointer; }
.tbl-name { font-weight: 500; color: var(--ink); }
.tbl-mono { font-family: var(--mono); font-size: 11px; color: var(--ink-2); }
.tbl-empty { text-align: center; color: var(--ink-3); padding: 24px; }
.tbl-acts { display: flex; gap: 4px; }

.roles-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
.role-card { background: var(--surface); border: 1px solid var(--bdr); border-radius: var(--r-md); overflow: hidden; }
.role-card-header { display: flex; align-items: center; gap: 8px; padding: 8px 12px; background: linear-gradient(180deg, #f4f6f8 0%, #e8ecf0 100%); border-bottom: 1px solid var(--bdr); }
.role-label { font-size: 12px; font-weight: 600; color: var(--ink); flex: 1; }
.role-count { font-size: 11px; color: var(--ink-3); }
.role-desc { padding: 8px 12px 4px; font-size: 12px; color: var(--ink-2); margin: 0; }
.role-perms { margin: 0; padding: 4px 12px 10px 26px; display: flex; flex-direction: column; gap: 3px; }
.role-perms li { font-size: 11px; color: var(--ink-3); }
</style>
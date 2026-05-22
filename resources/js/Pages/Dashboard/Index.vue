<script setup>
import { ref } from 'vue'
import ErTable from '@/Components/Ui/ErTable.vue'
import ErBadge from '@/Components/Ui/ErBadge.vue'
import ErTabs from '@/Components/Ui/ErTabs.vue'
import ErEmpty from '@/Components/Ui/ErEmpty.vue'
import ErButton from '@/Components/Ui/ErButton.vue'
import { RefreshCw, Plus } from 'lucide-vue-next'
import { Head, Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const props = defineProps({
    bots: Array,
    filters: Object,
    can: Object,
})

const activeTab = ref('list')
const search = ref(props.filters?.search ?? '')
const filterOpen = ref(true)
const selected = ref(new Set())

function doSearch() {
    router.get('/', { search: search.value || undefined }, { preserveState: true })
}

function createBot() {
    const name = prompt('Название бота:')
    if (name) {
        router.post(route('bots.store'), { name })
    }
}

function toggleAll(e) {
    if (e.target.checked) {
        props.bots.forEach(b => selected.value.add(b.id))
    } else {
        selected.value.clear()
    }
}

function toggleRow(id) {
    selected.value.has(id) ? selected.value.delete(id) : selected.value.add(id)
}

function formatDate(d) {
    return new Date(d).toLocaleString('ru', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

function formatSize(bytes) {
    if (!bytes) return '—'
    if (bytes < 1024) return bytes + ' Б'
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' КБ'
    return (bytes / 1024 / 1024).toFixed(1) + ' МБ'
}
</script>

<template>
    <Head title="Боты" />
    <AuthenticatedLayout title="Боты">
        <template #subbar>
            <ErTabs
                v-model="activeTab"
                :tabs="[
                    { value: 'list', label: 'Список ботов' },
                    { value: 'templates', label: 'Шаблоны', disabled: true },
                    { value: 'import-export', label: 'Импорт-Экспорт', disabled: true },
                    { value: 'trash', label: 'Корзина', disabled: true },
                ]"
            />
        </template>

        <template #actions>
            <ErButton v-if="can.createBot" variant="primary" @click="createBot">
                <Plus :size="13" />Новый бот
            </ErButton>
        </template>

        <!-- Filter fieldset -->
        <div class="filter-box" style="margin-bottom: 12px;">
            <div class="filter-h" @click="filterOpen = !filterOpen">
                Фильтр
                <span class="filter-toggle">{{ filterOpen ? '▲' : '▼' }}</span>
            </div>
            <div v-show="filterOpen" class="filter-b">
                <div class="filter-grid">
                    <div class="flt-fld">
                        <label class="flt-l">Название</label>
                        <input v-model="search" type="text" class="er-inp" placeholder="Поиск..." @keyup.enter="doSearch" />
                    </div>
                </div>
                <div class="filter-acts">
                    <ErButton variant="primary" size="sm" @click="doSearch">Найти</ErButton>
                    <ErButton size="sm" @click="search = ''; doSearch()">Сбросить</ErButton>
                </div>
            </div>
        </div>

        <!-- Table -->
        <ErTable>
            <template #toolbar>
                <select class="er-inp sm" style="width: 130px;">
                    <option value="">Действие…</option>
                    <option value="delete">Удалить выбранные</option>
                </select>
                <ErButton size="sm" :disabled="selected.size === 0">Применить</ErButton>
                <span v-if="selected.size > 0" class="tbl-sel-info">Выбрано {{ selected.size }} из {{ bots.length }}</span>
                <div style="flex: 1;"></div>
                <ErButton size="sm" @click="router.reload()"><RefreshCw :size="12" /></ErButton>
            </template>

            <template #thead>
                <tr>
                    <th style="width: 32px;"><input type="checkbox" @change="toggleAll" /></th>
                    <th style="width: 8px;"></th>
                    <th style="width: 48px;">ID</th>
                    <th>Название</th>
                    <th style="text-align: right;">Маршрутов</th>
                    <th style="text-align: right;">Диалогов</th>
                    <th style="text-align: right;">Подключений</th>
                    <th style="text-align: right;">Медиа</th>
                    <th style="text-align: right;">Размер медиа</th>
                    <th>Владелец</th>
                    <th>Обновлён</th>
                </tr>
            </template>

            <tr v-if="bots.length === 0">
                <td colspan="11">
                    <ErEmpty
                        compact
                        title="Нет ботов"
                        :text="filters?.search ? `По запросу «${filters.search}» ничего не найдено` : 'Создайте первого бота'"
                    />
                </td>
            </tr>
            <tr
                v-for="bot in bots"
                :key="bot.id"
                :class="{ sel: selected.has(bot.id) }"
                class="bot-row"
                @dblclick="router.visit(route('bots.edit', bot.id))"
            >
                <td><input type="checkbox" :checked="selected.has(bot.id)" @change="toggleRow(bot.id)" /></td>
                <td><span class="status-dot"></span></td>
                <td class="tbl-mono">{{ bot.id }}</td>
                <td>
                    <Link :href="route('bots.edit', bot.id)" class="tbl-link">{{ bot.name }}</Link>
                    <div v-if="bot.description" class="tbl-sub">{{ bot.description }}</div>
                </td>
                <td class="tbl-num">{{ bot.routes_count || '—' }}</td>
                <td class="tbl-num">{{ bot.flows_count || '—' }}</td>
                <td class="tbl-num">{{ bot.connections_count || '—' }}</td>
                <td class="tbl-num">{{ bot.used_media_count || '—' }}</td>
                <td class="tbl-num tbl-muted">{{ formatSize(bot.used_media_size) }}</td>
                <td class="tbl-muted">{{ bot.updater?.name ?? '—' }}</td>
                <td class="tbl-mono">{{ formatDate(bot.updated_at) }}</td>
            </tr>

            <template #paging>
                <span>Записи 1—{{ bots.length }} из {{ bots.length }}</span>
            </template>
        </ErTable>
    </AuthenticatedLayout>
</template>

<style scoped>
.filter-box { background: var(--surface); border: 1px solid var(--bdr); border-radius: var(--r-md); }
.filter-h { padding: 7px 12px; font-size: 12px; font-weight: 600; color: var(--ink); background: linear-gradient(180deg, #f4f6f8 0%, #e8ecf0 100%); border-radius: var(--r-md) var(--r-md) 0 0; display: flex; justify-content: space-between; cursor: pointer; user-select: none; }
.filter-toggle { font-size: 10px; color: var(--ink-3); }
.filter-b { padding: 10px 16px; }
.filter-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 8px 16px; margin-bottom: 10px; }
.flt-fld { display: flex; flex-direction: column; gap: 3px; }
.flt-l { font-size: 11px; color: var(--ink-3); font-weight: 500; }
.filter-acts { display: flex; gap: 6px; }

.er-inp { height: 26px; padding: 0 8px; border: 1px solid var(--bdr-d); border-radius: var(--r-sm); background: #fff; color: var(--ink); font-size: 12px; font-family: var(--font); width: 100%; box-shadow: inset 0 1px 1px rgba(0,0,0,.06); }
.er-inp:focus { outline: none; border-color: var(--blue); box-shadow: 0 0 0 2px rgba(58,114,196,.2); }
.er-inp.sm { height: 22px; font-size: 11px; }

.tbl-sel-info { font-size: 11px; color: var(--ink-3); }
.status-dot { display: inline-block; width: 7px; height: 7px; border-radius: 50%; background: var(--green); }
.tbl-link { color: var(--blue); text-decoration: none; font-weight: 500; }
.tbl-link:hover { text-decoration: underline; }
.tbl-sub { font-size: 11px; color: var(--ink-3); margin-top: 1px; }
.tbl-num { text-align: right; font-family: var(--mono); font-size: 12px; }
.tbl-muted { color: var(--ink-2); }
.tbl-mono { font-family: var(--mono); font-size: 11px; color: var(--ink-2); white-space: nowrap; }
.tbl-acts { display: flex; gap: 4px; }
.bot-row { cursor: pointer; }
</style>

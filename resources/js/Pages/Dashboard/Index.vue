<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import ErButton from '@/Components/Ui/ErButton.vue'
import ErTable from '@/Components/Ui/ErTable.vue'
import ErBadge from '@/Components/Ui/ErBadge.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { RefreshCw, Plus } from 'lucide-vue-next'

const props = defineProps({
    bots: Array,
    filters: Object,
    can: Object,
})

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
</script>

<template>
    <Head title="Боты" />
    <AuthenticatedLayout title="Боты">
        <template #subbar>
            <button class="er-tab act">Список ботов</button>
            <button class="er-tab">Шаблоны</button>
            <button class="er-tab">Импорт-Экспорт</button>
            <button class="er-tab">Корзина</button>
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
                    <th>Название</th>
                    <th style="text-align: right;">Маршрутов</th>
                    <th style="text-align: right;">Диалогов</th>
                    <th>Владелец</th>
                    <th>Обновлён</th>
                    <th style="width: 60px;"></th>
                </tr>
            </template>

            <tr v-if="bots.length === 0">
                <td colspan="8" class="tbl-empty">
                    Нет ботов{{ filters?.search ? ' по запросу «' + filters.search + '»' : '' }}
                </td>
            </tr>
            <tr
                v-for="bot in bots"
                :key="bot.id"
                :class="{ sel: selected.has(bot.id) }"
            >
                <td><input type="checkbox" :checked="selected.has(bot.id)" @change="toggleRow(bot.id)" /></td>
                <td><span class="status-dot"></span></td>
                <td>
                    <Link :href="route('bots.edit', bot.id)" class="tbl-link">{{ bot.name }}</Link>
                    <div v-if="bot.description" class="tbl-sub">{{ bot.description }}</div>
                </td>
                <td class="tbl-num">{{ bot.routes_count ?? 0 }}</td>
                <td class="tbl-num">{{ bot.flows_count ?? 0 }}</td>
                <td class="tbl-muted">{{ bot.updater?.name ?? '—' }}</td>
                <td class="tbl-mono">{{ formatDate(bot.updated_at) }}</td>
                <td>
                    <div class="tbl-acts">
                        <Link :href="route('bots.edit', bot.id)" class="tbl-act-btn">Открыть</Link>
                    </div>
                </td>
            </tr>

            <template #paging>
                <span>Записи 1—{{ bots.length }} из {{ bots.length }}</span>
            </template>
        </ErTable>
    </AuthenticatedLayout>
</template>

<style scoped>
.er-tab { padding: 0 14px; display: flex; align-items: center; color: var(--ink-2); cursor: pointer; font-weight: 500; border-bottom: 2px solid transparent; margin-bottom: -1px; font-size: 12px; background: none; border-top: none; border-left: none; border-right: none; font-family: var(--font); transition: color .1s; }
.er-tab:hover { color: var(--ink); }
.er-tab.act { color: var(--blue-d); border-bottom-color: var(--blue); background: linear-gradient(180deg, rgba(58,114,196,.04) 0%, transparent 100%); font-weight: 600; }

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
.tbl-empty { text-align: center; color: var(--ink-3); padding: 24px; }
.tbl-acts { display: flex; gap: 4px; }
.tbl-act-btn { font-size: 11px; color: var(--blue); text-decoration: none; padding: 1px 6px; border: 1px solid var(--bdr-l); border-radius: 2px; background: var(--surface); }
.tbl-act-btn:hover { background: var(--blue-soft); }
</style>
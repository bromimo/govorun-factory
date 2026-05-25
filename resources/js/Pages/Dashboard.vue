<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import ErCounterCard from '@/Components/Ui/ErCounterCard.vue'
import ErTable from '@/Components/Ui/ErTable.vue'
import ErBadge from '@/Components/Ui/ErBadge.vue'
import { Head } from '@inertiajs/vue3'
import { Bot, Activity, AlertTriangle, Users } from 'lucide-vue-next'

const counters = [
    { label: 'Ботов', value: '—', color: 'blue', icon: Bot },
    { label: 'Активных 24ч', value: '—', color: 'green', icon: Activity },
    { label: 'Предупреждений', value: '—', color: 'orange', icon: AlertTriangle },
    { label: 'Ошибок', value: '—', color: 'red', icon: AlertTriangle },
]
</script>

<template>
    <Head title="Сводка" />
    <AuthenticatedLayout title="Сводка системы">
        <template #subbar>
            <button class="er-tab act">Сводка</button>
            <button class="er-tab">Активность</button>
            <button class="er-tab">События</button>
            <button class="er-tab">Здоровье системы</button>
        </template>

        <!-- Counter cards -->
        <div class="counter-row" style="margin-bottom: 16px;">
            <ErCounterCard
                v-for="c in counters"
                :key="c.label"
                :label="c.label"
                :value="c.value"
                :color="c.color"
                :icon="c.icon"
            />
        </div>

        <!-- Two-column grid -->
        <div class="dash-grid">
            <div>
                <ErTable>
                    <template #toolbar>
                        <span style="font-size: 11px; font-weight: 600; color: var(--ink-2);">Активность ботов</span>
                    </template>
                    <template #thead>
                        <tr>
                            <th></th>
                            <th>Название</th>
                            <th>Статус</th>
                            <th style="text-align: right;">Сообщ./ч</th>
                            <th style="text-align: right;">Ошибок</th>
                        </tr>
                    </template>
                    <tr>
                        <td colspan="5" class="tbl-empty">Данные недоступны</td>
                    </tr>
                </ErTable>
            </div>

            <div>
                <div class="er-widget" style="margin-bottom: 12px;">
                    <div class="er-widget-h">Состояние системы</div>
                    <div class="er-widget-b er-widget-items">
                        <div class="er-widget-row">
                            <span>База данных</span>
                            <ErBadge color="nt">—</ErBadge>
                        </div>
                        <div class="er-widget-row">
                            <span>Очередь</span>
                            <ErBadge color="nt">—</ErBadge>
                        </div>
                        <div class="er-widget-row">
                            <span>Хранилище</span>
                            <ErBadge color="nt">—</ErBadge>
                        </div>
                    </div>
                </div>

                <div class="er-widget">
                    <div class="er-widget-h">Последние действия</div>
                    <div class="er-widget-b tbl-empty">Данные недоступны</div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.er-tab { padding: 0 14px; display: flex; align-items: center; color: var(--ink-2); cursor: pointer; font-weight: 500; border-bottom: 2px solid transparent; margin-bottom: -1px; font-size: 12px; background: none; border-top: none; border-left: none; border-right: none; font-family: var(--font); transition: color .1s; }
.er-tab.act { color: var(--blue-d); border-bottom-color: var(--blue); font-weight: 600; }
.counter-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; }
.dash-grid { display: grid; grid-template-columns: 1fr 300px; gap: 14px; }
.er-widget { background: var(--surface); border: 1px solid var(--bdr); border-radius: var(--r-md); }
.er-widget-h { padding: 6px 10px; font-size: 11px; font-weight: 600; color: var(--ink-2); background: linear-gradient(180deg, #f4f6f8 0%, #e8ecf0 100%); border-bottom: 1px solid var(--bdr); text-transform: uppercase; letter-spacing: .04em; border-radius: var(--r-md) var(--r-md) 0 0; }
.er-widget-b { padding: 8px 10px; }
.er-widget-items { display: flex; flex-direction: column; gap: 6px; }
.er-widget-row { display: flex; justify-content: space-between; align-items: center; font-size: 12px; color: var(--ink-2); }
.tbl-empty { text-align: center; color: var(--ink-3); padding: 20px; font-size: 12px; }
</style>

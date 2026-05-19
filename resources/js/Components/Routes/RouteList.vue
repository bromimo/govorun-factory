<script setup>
import { ref, watch, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { controllerBlockTypes } from '../Blocks/blockTypes.js';
import RouteEditor from './RouteEditor.vue';
import ErTable from '@/Components/Ui/ErTable.vue';
import ErBadge from '@/Components/Ui/ErBadge.vue';
import ErButton from '@/Components/Ui/ErButton.vue';
import { GripVertical, Pencil, Trash2, Plus } from 'lucide-vue-next';

const props = defineProps({
    botId: Number,
    routes: Array,
    flows: Array,
    canUpdate: Boolean,
});

const showEditor = ref(false);
const editingRoute = ref(null);
const editorParentId = ref(null);
const localRoutes = ref(props.routes ? [...props.routes] : []);
const dragIndex = ref(null);
const overIndex = ref(null);

const sortedRoutes = computed(() => {
    const list = [...localRoutes.value];
    const idx = list.findIndex(r => r.type === 'fallback');
    if (idx === -1) return list;
    const [fb] = list.splice(idx, 1);
    list.push(fb);
    return list;
});

const hasFallback = computed(() => localRoutes.value.some(r => r.type === 'fallback'));

watch(() => props.routes, (val) => { localRoutes.value = [...val]; });

function openCreate(parentId = null) {
    editingRoute.value = null;
    editorParentId.value = parentId;
    showEditor.value = true;
}

function openEdit(route) {
    editingRoute.value = route;
    editorParentId.value = route.parent_id ?? null;
    showEditor.value = true;
}

function closeEditor() {
    showEditor.value = false;
    editingRoute.value = null;
    editorParentId.value = null;
}

function deleteRoute(route) {
    if (confirm('Удалить маршрут?')) {
        router.delete(window.route('bot-routes.destroy', [props.botId, route.id]));
    }
}

function onDragStart(e, index) {
    dragIndex.value = index;
    e.dataTransfer.effectAllowed = 'move';
}

function onDragOver(e, index) {
    if (sortedRoutes.value[index]?.type === 'fallback') return;
    e.preventDefault();
    overIndex.value = index;
}

function onDrop() {
    if (dragIndex.value === null || overIndex.value === null || dragIndex.value === overIndex.value) return;

    const list = [...sortedRoutes.value];
    if (list[dragIndex.value]?.type === 'fallback') return;
    const [moved] = list.splice(dragIndex.value, 1);
    list.splice(overIndex.value, 0, moved);

    const fbIdx = list.findIndex(r => r.type === 'fallback');
    if (fbIdx !== -1 && fbIdx !== list.length - 1) {
        const [fb] = list.splice(fbIdx, 1);
        list.push(fb);
    }
    localRoutes.value = list;

    const ids = list.map(r => r.id);
    window.axios.post(window.route('bot-routes.reorder', props.botId), { ids });
}

function onDragEnd() {
    dragIndex.value = null;
    overIndex.value = null;
}

function routeBadgeColor(type) {
    const map = {
        command: 'bl', phrase: 'bl', pattern: 'or',
        action: 'nt', event: 'nt', media: 'nt',
        location: 'nt', contact: 'nt', referral: 'yl',
        fallback: 'rd',
    };
    return map[type] ?? 'nt';
}

function routeBlockSummary(route) {
    if (route.handler_type !== 'controller') return null;
    const block = (route.handler_schema?.blocks ?? [])[0];
    if (!block) return null;
    const bt = controllerBlockTypes.find(b => b.type === block.type);
    const raw = block.type === 'api_call'
        ? (block.params?.url ?? '')
        : block.type === 'save_state'
            ? (block.params?.variables ?? []).map(v => v.key).filter(Boolean).join(', ')
            : (block.params?.text ?? '').replace(/<[^>]*>/g, '');
    const full = raw.trim();
    const short = full.length > 28 ? full.slice(0, 28) + '…' : full;
    return { label: bt?.label ?? block.type, short, full };
}

const blockSummaries = computed(() => {
    const map = {};
    for (const r of localRoutes.value) {
        const s = routeBlockSummary(r);
        if (s) map[r.id] = s;
        for (const c of r.children ?? []) {
            const cs = routeBlockSummary(c);
            if (cs) map[c.id] = cs;
        }
    }
    return map;
});
</script>

<template>
    <div>
        <ErTable>
            <template #toolbar>
                <select class="er-inp sm" style="width: 130px;">
                    <option value="">Действие…</option>
                    <option value="delete">Удалить</option>
                </select>
                <ErButton size="sm">Применить</ErButton>
                <div style="flex: 1;"></div>
                <ErButton v-if="canUpdate" variant="primary" size="sm" @click="openCreate()">
                    <Plus :size="12" />Добавить
                </ErButton>
            </template>

            <template #thead>
                <tr>
                    <th style="width: 24px;"></th>
                    <th style="width: 28px;"><input type="checkbox" /></th>
                    <th style="width: 32px;">№</th>
                    <th>Тип</th>
                    <th>Паттерн / Команда</th>
                    <th>Обработчик</th>
                    <th>Статус</th>
                    <th style="width: 80px;"></th>
                </tr>
            </template>

            <template v-if="sortedRoutes.length">
                <template v-for="(route, index) in sortedRoutes" :key="route.id">
                    <tr
                        :class="{ sel: overIndex === index && dragIndex !== index }"
                        :draggable="canUpdate && route.type !== 'fallback'"
                        @dragstart="onDragStart($event, index)"
                        @dragover="onDragOver($event, index)"
                        @drop="onDrop"
                        @dragend="onDragEnd"
                    >
                        <td>
                            <GripVertical
                                v-if="canUpdate && route.type !== 'fallback'"
                                :size="14"
                                class="drag-handle"
                            />
                        </td>
                        <td><input type="checkbox" /></td>
                        <td class="tbl-mono">{{ index + 1 }}</td>
                        <td>
                            <ErBadge :color="routeBadgeColor(route.type)">{{ route.type }}</ErBadge>
                        </td>
                        <td class="tbl-mono">{{ route.match || route.command || '—' }}</td>
                        <td class="tbl-mono tbl-handler">
                            <span v-if="blockSummaries[route.id]" :title="blockSummaries[route.id].full || undefined">
                                {{ blockSummaries[route.id].label }}<span v-if="blockSummaries[route.id].short"> · {{ blockSummaries[route.id].short }}</span>
                            </span>
                            <span v-else>{{ route.handler_type }}</span>
                        </td>
                        <td>
                            <ErBadge color="gr" dot>активен</ErBadge>
                        </td>
                        <td>
                            <div v-if="canUpdate" class="tbl-acts">
                                <button
                                    v-if="route.type === 'phrase' && !route.parent_id"
                                    class="tbl-act-btn"
                                    title="Добавить вложенный"
                                    @click="openCreate(route.id)"
                                >
                                    <Plus :size="12" />
                                </button>
                                <button class="tbl-act-btn" title="Изменить" @click="openEdit(route)">
                                    <Pencil :size="12" />
                                </button>
                                <button class="tbl-act-btn" title="Удалить" @click="deleteRoute(route)">
                                    <Trash2 :size="12" />
                                </button>
                            </div>
                        </td>
                    </tr>
                    <!-- Дочерние маршруты -->
                    <tr v-for="child in route.children ?? []" :key="child.id" class="child-row">
                        <td></td>
                        <td><input type="checkbox" /></td>
                        <td class="tbl-mono tbl-child-idx">↳</td>
                        <td>
                            <ErBadge :color="routeBadgeColor(child.type)">{{ child.type }}</ErBadge>
                        </td>
                        <td class="tbl-mono">{{ child.match || child.command || '—' }}</td>
                        <td class="tbl-mono tbl-handler">
                            <span v-if="blockSummaries[child.id]" :title="blockSummaries[child.id].full || undefined">
                                {{ blockSummaries[child.id].label }}<span v-if="blockSummaries[child.id].short"> · {{ blockSummaries[child.id].short }}</span>
                            </span>
                            <span v-else>{{ child.handler_type }}</span>
                        </td>
                        <td>
                            <ErBadge color="gr" dot>активен</ErBadge>
                        </td>
                        <td>
                            <div v-if="canUpdate" class="tbl-acts">
                                <button class="tbl-act-btn" title="Изменить" @click="openEdit(child)">
                                    <Pencil :size="12" />
                                </button>
                                <button class="tbl-act-btn" title="Удалить" @click="deleteRoute(child)">
                                    <Trash2 :size="12" />
                                </button>
                            </div>
                        </td>
                    </tr>
                </template>
            </template>
            <tr v-else>
                <td colspan="8" class="tbl-empty">Нет маршрутов</td>
            </tr>

            <template #paging>
                <span>{{ sortedRoutes.length }} маршрутов</span>
                <span style="color: var(--ink-4); font-size: 10px;">Перетаскивайте за ⋮⋮ для изменения приоритета</span>
            </template>
        </ErTable>

        <RouteEditor
            v-if="showEditor"
            :bot-id="botId"
            :route="editingRoute"
            :parent-id="editorParentId"
            :has-children="!!editingRoute?.children?.length"
            :has-fallback="hasFallback"
            :flows="flows"
            @close="closeEditor"
        />
    </div>
</template>

<style scoped>
.drag-handle { cursor: grab; color: var(--ink-4); }
.drag-handle:active { cursor: grabbing; }
.tbl-mono { font-family: var(--mono); font-size: 11px; }
.tbl-handler { max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.tbl-acts { display: flex; gap: 2px; }
.tbl-act-btn { padding: 2px 5px; border: 1px solid var(--bdr-l); border-radius: 2px; background: var(--surface); color: var(--ink-3); cursor: pointer; display: flex; align-items: center; }
.tbl-act-btn:hover { background: var(--blue-soft); color: var(--blue-d); }
.tbl-child-idx { color: var(--ink-4); }
.tbl-empty { text-align: center; padding: 20px; color: var(--ink-4); font-size: 12px; }
.er-inp { height: 26px; padding: 0 8px; border: 1px solid var(--bdr-d); border-radius: var(--r-sm); background: #fff; color: var(--ink); font-size: 12px; font-family: var(--font); width: 100%; box-shadow: inset 0 1px 1px rgba(0,0,0,.06); }
.er-inp.sm { height: 22px; font-size: 11px; }
.er-inp:focus { outline: none; border-color: var(--blue); box-shadow: 0 0 0 2px rgba(58,114,196,.2); }
</style>
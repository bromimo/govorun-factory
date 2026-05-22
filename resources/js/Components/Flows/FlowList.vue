<script setup>
import { ref } from 'vue';
import { Plus } from 'lucide-vue-next';
import Modal from '@/Components/Modal.vue';
import { Link, router } from '@inertiajs/vue3';
import ErTable from '@/Components/Ui/ErTable.vue';
import ErButton from '@/Components/Ui/ErButton.vue';
import { useToast } from '@/composables/useToast';
import StatusBadge from '@/Components/Ui/StatusBadge.vue';
import ConfirmModal from '@/Components/Ui/ConfirmModal.vue';

const props = defineProps({
    botId: Number,
    flows: Array,
    canUpdate: Boolean,
});

const editingFlow = ref(null);
const editName = ref('');
const editDesc = ref('');

function goToFlow(flow) {
    router.visit(route('bot-flows.show', [props.botId, flow.id]));
}

function openCreate() {
    editingFlow.value = { id: null };
    editName.value = '';
    editDesc.value = '';
}

function openEdit(flow) {
    editingFlow.value = flow;
    editName.value = flow.name;
    editDesc.value = flow.description ?? '';
}

function closeModal() {
    editingFlow.value = null;
}

function save() {
    if (!editName.value.trim()) return;
    if (editingFlow.value.id) {
        router.patch(
            route('bot-flows.update', [props.botId, editingFlow.value.id]),
            { name: editName.value.trim(), description: editDesc.value.trim() },
            { onSuccess: closeModal },
        );
    } else {
        router.post(
            route('bot-flows.store', props.botId),
            { name: editName.value.trim(), description: editDesc.value.trim() },
            { onSuccess: closeModal },
        );
    }
}

function formatSize(bytes) {
    if (!bytes) return '—'
    if (bytes < 1024) return bytes + ' Б'
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' КБ'
    return (bytes / 1024 / 1024).toFixed(1) + ' МБ'
}

const confirmFlow = ref(null);
const toast = useToast();
const statusFlow = ref(null);
const statusValue = ref(null);
const impactRoutes = ref([]);

function deleteFlow(flow) {
    confirmFlow.value = flow;
}

function doDeleteFlow() {
    router.delete(route('bot-flows.destroy', [props.botId, confirmFlow.value.id]));
    confirmFlow.value = null;
}

async function requestStatusChange(f, value) {
    if (value === 'inactive' || value === 'draft') {
        try {
            const { data } = await window.axios.get(
                window.route('bot-flows.status-impact', [props.botId, f.id])
            );
            impactRoutes.value = data.affected_routes ?? [];
            statusFlow.value = f;
            statusValue.value = value;
        } catch {
            cancelStatusChange();
            toast.error('Не удалось получить данные о влиянии статуса');
        }
    } else {
        applyStatus(f, value);
    }
}

function applyStatus(f, value) {
    const old = f.status;
    f.status = value;
    window.axios.patch(
        window.route('bot-flows.change-status', [props.botId, f.id]),
        { status: value }
    )
        .catch((err) => {
            f.status = old;
            const rawErrors = err.response?.data?.errors;
            const errors = Array.isArray(rawErrors)
                ? rawErrors
                : rawErrors && typeof rawErrors === 'object'
                    ? Object.values(rawErrors).flat()
                    : ['Не удалось сменить статус'];
            for (const e of errors.slice(0, 5)) toast.error(e);
            if (errors.length > 5) toast.error(`и ещё ${errors.length - 5} ошибок`);
        });
    statusFlow.value = null;
    statusValue.value = null;
    impactRoutes.value = [];
}

function cancelStatusChange() {
    statusFlow.value = null;
    statusValue.value = null;
    impactRoutes.value = [];
}
</script>

<template>
    <div>
        <ErTable>
            <template #toolbar>
                <div style="flex: 1;"></div>
                <ErButton v-if="canUpdate" variant="primary" size="sm" @click="openCreate">
                    <Plus :size="12" />Добавить
                </ErButton>
            </template>

            <template #thead>
                <tr>
                    <th style="width: 28px;"><input type="checkbox" /></th>
                    <th style="width: 48px;">ID</th>
                    <th>Название</th>
                    <th style="text-align: right;">Блоков</th>
                    <th style="text-align: right;">Вопросов</th>
                    <th style="text-align: right;">API</th>
                    <th style="text-align: right;">Медиа</th>
                    <th style="text-align: right;">Размер медиа</th>
                    <th>Статус</th>
                    <th style="width: 80px;"></th>
                </tr>
            </template>

            <template v-if="flows.length">
                <tr
                    v-for="f in flows"
                    :key="f.id"
                    class="flow-row"
                    @dblclick="goToFlow(f)"
                >
                    <td><input type="checkbox" /></td>
                    <td class="tbl-mono">{{ f.id }}</td>
                    <td>
                        <Link :href="route('bot-flows.show', [botId, f.id])" class="tbl-link">{{ f.name }}</Link>
                        <div v-if="f.description" class="tbl-sub">{{ f.description }}</div>
                    </td>
                    <td class="tbl-num">{{ f.blocks_count || '—' }}</td>
                    <td class="tbl-num">{{ f.ask_count || '—' }}</td>
                    <td class="tbl-num">{{ f.api_call_count || '—' }}</td>
                    <td class="tbl-num">{{ f.used_media_count || '—' }}</td>
                    <td class="tbl-num tbl-muted">{{ formatSize(f.used_media_size) }}</td>
                    <td>
                        <StatusBadge :status="f.status" :disabled="!canUpdate" @change="(v) => requestStatusChange(f, v)" />
                    </td>
                    <td>
                        <div class="tbl-acts">
                            <button v-if="canUpdate" class="tbl-act-btn" @click.stop="openEdit(f)">Изменить</button>
                            <button v-if="canUpdate" class="tbl-act-btn tbl-act-btn--danger" @click.stop="deleteFlow(f)">Удалить</button>
                        </div>
                    </td>
                </tr>
            </template>
            <tr v-else>
                <td colspan="10" class="tbl-empty">Нет диалогов</td>
            </tr>

            <template #paging>
                <span>{{ flows.length }} диалогов</span>
            </template>
        </ErTable>

        <Modal
            v-if="editingFlow"
            :show="true"
            :title="editingFlow.id ? 'Редактировать диалог' : 'Новый диалог'"
            max-width="sm"
            @close="closeModal"
        >
            <div class="fl-body">
                <div class="fl-field">
                    <label class="fl-lbl">Название</label>
                    <input
                        v-model="editName"
                        class="fl-inp"
                        placeholder="Название диалога"
                        autofocus
                        @keydown.enter="save"
                    />
                </div>
                <div class="fl-field">
                    <label class="fl-lbl">Описание</label>
                    <input
                        v-model="editDesc"
                        class="fl-inp"
                        placeholder="Необязательно"
                        @keydown.enter="save"
                    />
                </div>
            </div>
            <div class="fl-foot">
                <ErButton @click="closeModal">Отмена</ErButton>
                <ErButton variant="primary" :disabled="!editName.trim()" @click="save">
                    {{ editingFlow.id ? 'Сохранить' : 'Создать' }}
                </ErButton>
            </div>
        </Modal>

        <ConfirmModal
            :show="!!confirmFlow"
            title="Удалить диалог?"
            :message="`Диалог «${confirmFlow?.name}» будет удалён безвозвратно.`"
            @confirm="doDeleteFlow"
            @cancel="confirmFlow = null"
        />

        <ConfirmModal
            :show="!!statusFlow"
            title="Сменить статус диалога?"
            :message="impactRoutes.length
                ? `${impactRoutes.length} маршрут(ов) будут переведены в черновик.`
                : 'Подтвердите смену статуса.'"
            @confirm="applyStatus(statusFlow, statusValue)"
            @cancel="cancelStatusChange"
        />
    </div>
</template>

<style scoped>
.flow-row { cursor: pointer; }
.flow-row:hover td { background: var(--surface-2); }
.tbl-link { color: var(--blue); text-decoration: none; font-weight: 500; }
.tbl-link:hover { text-decoration: underline; }
.tbl-sub { font-size: 11px; color: var(--ink-3); margin-top: 1px; }
.tbl-num { text-align: right; font-family: var(--mono); font-size: 12px; }
.tbl-muted { color: var(--ink-2); }
.tbl-acts { display: flex; gap: 2px; }
.tbl-empty { text-align: center; padding: 20px; color: var(--ink-4); font-size: 12px; }

.fl-body { padding: 16px 20px; display: flex; flex-direction: column; gap: 12px; }
.fl-field { display: flex; flex-direction: column; gap: 4px; }
.fl-lbl { font-size: 11px; font-weight: 600; color: var(--ink-2); }
.fl-inp { height: 28px; padding: 0 8px; border: 1px solid var(--bdr-d); border-radius: var(--r-sm); font-size: 12px; font-family: var(--font); color: var(--ink); background: #fff; }
.fl-inp:focus { outline: none; border-color: var(--blue); box-shadow: 0 0 0 2px rgba(58,114,196,.18); }
.fl-foot { display: flex; justify-content: flex-end; gap: 8px; padding: 10px 20px 14px; border-top: 1px solid var(--bdr-l); background: linear-gradient(180deg, #f4f6f8 0%, #e8ecf0 100%); }
</style>
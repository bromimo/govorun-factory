<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { Plus, Pencil, Trash2 } from 'lucide-vue-next';
import ErTable from '@/Components/Ui/ErTable.vue';
import ErButton from '@/Components/Ui/ErButton.vue';
import Modal from '@/Components/Modal.vue';

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

function deleteFlow(flow) {
    if (confirm(`Удалить диалог «${flow.name}»?`)) {
        router.delete(route('bot-flows.destroy', [props.botId, flow.id]));
    }
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
                    <th>Название</th>
                    <th>Описание</th>
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
                    <td class="flow-name">{{ f.name }}</td>
                    <td class="flow-desc">{{ f.description || '—' }}</td>
                    <td>
                        <div class="tbl-acts">
                            <button v-if="canUpdate" class="tbl-act-btn" title="Изменить" @click.stop="openEdit(f)">
                                <Pencil :size="12" />
                            </button>
                            <button v-if="canUpdate" class="tbl-act-btn" title="Удалить" @click.stop="deleteFlow(f)">
                                <Trash2 :size="12" />
                            </button>
                        </div>
                    </td>
                </tr>
            </template>
            <tr v-else>
                <td colspan="3" class="tbl-empty">Нет диалогов</td>
            </tr>

            <template #paging>
                <span>{{ flows.length }} диалогов</span>
                <span style="color: var(--ink-4); font-size: 10px;">Двойной клик — открыть редактор</span>
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
    </div>
</template>

<style scoped>
.flow-row { cursor: pointer; }
.flow-row:hover td { background: var(--surface-2); }
.flow-name { font-weight: 500; color: var(--blue-d); }
.flow-desc { color: var(--ink-3); font-size: 11px; }
.tbl-acts { display: flex; gap: 2px; }
.tbl-act-btn { padding: 2px 5px; border: 1px solid var(--bdr-l); border-radius: 2px; background: var(--surface); color: var(--ink-3); cursor: pointer; display: flex; align-items: center; }
.tbl-act-btn:hover { background: var(--blue-soft); color: var(--blue-d); }
.tbl-empty { text-align: center; padding: 20px; color: var(--ink-4); font-size: 12px; }

.fl-body { padding: 16px 20px; display: flex; flex-direction: column; gap: 12px; }
.fl-field { display: flex; flex-direction: column; gap: 4px; }
.fl-lbl { font-size: 11px; font-weight: 600; color: var(--ink-2); }
.fl-inp { height: 28px; padding: 0 8px; border: 1px solid var(--bdr-d); border-radius: var(--r-sm); font-size: 12px; font-family: var(--font); color: var(--ink); background: #fff; }
.fl-inp:focus { outline: none; border-color: var(--blue); box-shadow: 0 0 0 2px rgba(58,114,196,.18); }
.fl-foot { display: flex; justify-content: flex-end; gap: 8px; padding: 10px 20px 14px; border-top: 1px solid var(--bdr-l); background: linear-gradient(180deg, #f4f6f8 0%, #e8ecf0 100%); }
</style>
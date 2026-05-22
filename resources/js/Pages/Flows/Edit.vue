<script setup>
import ErButton from '@/Components/Ui/ErButton.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import FlowCanvas from '@/Components/Flows/FlowCanvas.vue';
import ConfirmModal from '@/Components/Ui/ConfirmModal.vue';
import NodePalette from '@/Components/Flows/NodePalette.vue';
import StatusBadge from '@/Components/Ui/StatusBadge.vue';
import { useToast } from '@/composables/useToast';
import { ref, watch, computed, onMounted, onBeforeUnmount } from 'vue';
import KeyboardHints from '@/Components/Flows/KeyboardHints.vue';
import NodeProperties from '@/Components/Flows/NodeProperties.vue';
import EdgeProperties from '@/Components/Flows/EdgeProperties.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { debounce } from '@/utils/debounce';
import { useDirtyGuard } from '@/composables/useDirtyGuard';

const props = defineProps({
    bot: Object,
    flow: Object,
    can: Object,
    auto_drafted_reasons: Array,
});

const toast = useToast();
const canvasRef = ref(null);
const saving = ref(false);
const saved = ref(false);
const flowDirty = ref(false);
const showDirtyGuard = ref(false);
let dirtyResume = null;

const localFlowStatus = ref(props.flow.status);
const statusImpactRoutes = ref([]);
const pendingStatusValue = ref(null);

useDirtyGuard(() => flowDirty.value, (resume) => {
    dirtyResume = resume;
    showDirtyGuard.value = true;
});

function confirmDirtyGuard() {
    showDirtyGuard.value = false;
    dirtyResume?.();
    dirtyResume = null;
}

function cancelDirtyGuard() {
    showDirtyGuard.value = false;
    dirtyResume = null;
}

const description = ref(props.flow.description ?? '');
const panelWidth = ref(360);
const resizing = ref(false);
const showClearModal = ref(false);

function startResize(e) {
    resizing.value = true;
    const startX = e.clientX;
    const startWidth = panelWidth.value;

    function onMove(e) {
        panelWidth.value = Math.max(280, Math.min(800, startWidth + startX - e.clientX));
    }
    function onUp() {
        resizing.value = false;
        document.removeEventListener('mousemove', onMove);
        document.removeEventListener('mouseup', onUp);
    }
    document.addEventListener('mousemove', onMove);
    document.addEventListener('mouseup', onUp);
}

const selectedNode = computed(() => canvasRef.value?.selectedNode);
const selectedEdge = computed(() => canvasRef.value?.selectedEdge);
const allNodeIds = computed(() => canvasRef.value?.getAllNodeIds() ?? []);
const allStateKeys = computed(() => canvasRef.value?.getAllStateKeys() ?? []);
const declaredStateKeys = computed(() => {
    const node = selectedNode.value;
    if (!node || !canvasRef.value) return [];
    return canvasRef.value.getDeclaredStateKeysBefore(node.id);
});
const possiblyDeclaredStateKeys = computed(() => {
    const node = selectedNode.value;
    if (!node || !canvasRef.value) return [];
    return canvasRef.value.getPossiblyDeclaredStateKeysBefore(node.id);
});

const botValidationMessages = computed(() => props.bot.config?.validation_messages ?? {});

const siblingLabels = computed(() => {
    const edge = selectedEdge.value;
    if (!edge || !canvasRef.value) return [];
    return canvasRef.value.getOutgoingEdgeLabels(edge.source, edge.id);
});

const allNodes = computed(() => canvasRef.value?.getAllNodes() ?? []);
const hasNonStartNodes = computed(() => allNodes.value.some(n => n.type !== 'start'));

function onNodeDataUpdated(nodeId, newData) {
    canvasRef.value?.setNodeData(nodeId, newData);
    flowDirty.value = true;
}

function onNodeRenamed(oldId, newId) {
    canvasRef.value?.renameNode(oldId, newId);
}

function onEdgeLabelUpdated(edgeId, label) {
    canvasRef.value?.setEdgeLabel(edgeId, label);
}

function onClearWaypoints(edgeId) {
    canvasRef.value?.clearEdgeWaypoints(edgeId);
}

function save() {
    if (!canvasRef.value) return;
    if (saving.value) return;
    saving.value = true;
    saved.value = false;
    flowDirty.value = false;

    const graph = canvasRef.value.getGraph();

    router.put(route('bot-flows.update', [props.bot.id, props.flow.id]), {
        name: props.flow.name,
        description: description.value,
        graph,
    }, {
        preserveState: true,
        onSuccess: () => {
            saved.value = true;
            setTimeout(() => (saved.value = false), 2000);
        },
        onError: () => {
            flowDirty.value = true;
        },
        onFinish: () => { saving.value = false; },
    });
}

const saveDebounced = debounce(save, 500);

watch(() => props.auto_drafted_reasons, (reasons) => {
    if (reasons?.length) {
        toast.warning('Флоу переведён в черновик', { title: 'Автоматически' });
        for (const reason of reasons.slice(0, 3)) toast.warning(reason);
        localFlowStatus.value = 'draft';
    }
});

function handleSave() {
    saveDebounced.cancel();
    save();
}

async function requestStatusChange(value) {
    if (value === 'inactive' || value === 'draft') {
        try {
            const { data } = await window.axios.get(
                window.route('bot-flows.status-impact', [props.bot.id, props.flow.id])
            );
            statusImpactRoutes.value = data.affected_routes ?? [];
            pendingStatusValue.value = value;
        } catch {
            toast.error('Не удалось получить данные о влиянии');
        }
    } else {
        applyStatus(value);
    }
}

function applyStatus(value) {
    const old = localFlowStatus.value;
    localFlowStatus.value = value;
    window.axios.patch(
        window.route('bot-flows.change-status', [props.bot.id, props.flow.id]),
        { status: value }
    )
        .then(({ data }) => {
            localFlowStatus.value = data.status;
            toast.success('Статус изменён');
        })
        .catch((err) => {
            localFlowStatus.value = old;
            const rawErrors = err.response?.data?.errors;
            const errors = Array.isArray(rawErrors)
                ? rawErrors
                : rawErrors && typeof rawErrors === 'object'
                    ? Object.values(rawErrors).flat()
                    : ['Не удалось сменить статус'];
            for (const e of errors.slice(0, 5)) toast.error(e);
            if (errors.length > 5) toast.error(`и ещё ${errors.length - 5} ошибок`);
        });
    statusImpactRoutes.value = [];
    pendingStatusValue.value = null;
}

function cancelStatusChange() {
    statusImpactRoutes.value = [];
    pendingStatusValue.value = null;
}

onMounted(() => document.body.classList.add('overflow-hidden'));
onBeforeUnmount(() => {
    document.body.classList.remove('overflow-hidden');
    saveDebounced.cancel();
});

function fitView() {
    canvasRef.value?.doFitView();
}

function autoLayout() {
    canvasRef.value?.autoLayout();
}

function clearCanvas() {
    canvasRef.value?.clearCanvas();
    showClearModal.value = false;
}
</script>

<template>
    <Head :title="`Flow: ${flow.name}`" />
    <AuthenticatedLayout :flush="true">
        <template #subbar>
            <div class="fl-toolbar">
                <nav class="fl-bcr">
                    <Link :href="route('dashboard')">Главная</Link>
                    <span class="sep">›</span>
                    <Link :href="route('bots.edit', bot.id) + '?tab=flows'">{{ bot.name }}</Link>
                    <span class="sep">›</span>
                    <span>Флоу: {{ flow.name }}</span>
                </nav>
                <StatusBadge
                    v-if="can.update"
                    :status="localFlowStatus"
                    @change="requestStatusChange"
                />
                <span v-else class="save-state" style="font-size:11px">{{ localFlowStatus }}</span>
                <div style="flex: 1;" />
                <span class="save-state">
                    <template v-if="saving">Сохраняем…</template>
                    <template v-else-if="saved">Сохранено</template>
                </span>
                <ErButton v-if="can.update" size="sm" @click="autoLayout">Авто</ErButton>
                <ErButton size="sm" @click="fitView">Фит</ErButton>
                <ErButton v-if="can.update" variant="danger" size="sm" :disabled="!hasNonStartNodes" @click="showClearModal = true">Очистить</ErButton>
                <ErButton as="a" size="sm" :href="route('bots.edit', bot.id) + '?tab=flows'">Выйти</ErButton>
                <ErButton v-if="can.update" variant="primary" size="sm" :disabled="saving || !flowDirty" @click="handleSave">
                    Сохранить
                </ErButton>
            </div>
        </template>

        <div class="flow-layout">
            <NodePalette v-if="can.update" class="w-48 shrink-0" />

            <KeyboardHints />

            <FlowCanvas
                ref="canvasRef"
                :initial-nodes="flow.graph?.nodes ?? []"
                :initial-edges="flow.graph?.edges ?? []"
                :initial-viewport="flow.graph?.viewport ?? null"
            />

            <div v-if="selectedNode || selectedEdge" class="flex shrink-0" :style="{ width: panelWidth + 'px' }">
                <div @mousedown.prevent="startResize"
                    class="w-1 cursor-col-resize hover:bg-indigo-300 active:bg-indigo-400 transition-colors" />

                <NodeProperties
                    v-if="selectedNode"
                    :node="selectedNode"
                    :can-update="can.update"
                    :all-node-ids="allNodeIds"
                    :all-state-keys="allStateKeys"
                    :declared-state-keys="declaredStateKeys"
                    :possibly-declared-state-keys="possiblyDeclaredStateKeys"
                    :bot-validation-messages="botValidationMessages"
                    :bot-id="bot.id"
                    class="flex-1 min-w-0"
                    @update="onNodeDataUpdated"
                    @rename="onNodeRenamed"
                    @close="canvasRef.selectedNode = null"
                />

                <EdgeProperties
                    v-if="selectedEdge"
                    :edge="selectedEdge"
                    :can-update="can.update"
                    :sibling-labels="siblingLabels"
                    :all-nodes="allNodes"
                    class="flex-1 min-w-0"
                    @update="onEdgeLabelUpdated"
                    @clear-waypoints="onClearWaypoints"
                    @close="canvasRef.selectedEdge = null"
                />
            </div>
        </div>

        <ConfirmModal
            :show="showDirtyGuard"
            title="Несохранённые изменения"
            message="Есть несохранённые изменения. Уйти без сохранения?"
            confirm-label="Уйти"
            variant="default"
            @confirm="confirmDirtyGuard"
            @cancel="cancelDirtyGuard"
        />

        <ConfirmModal
            :show="showClearModal"
            title="Очистить флоу"
            message="Все ноды, кроме «Начало», и все связи будут удалены. Это действие нельзя отменить."
            confirm-label="Очистить"
            variant="danger"
            @confirm="clearCanvas"
            @cancel="showClearModal = false"
        />

        <ConfirmModal
            :show="!!pendingStatusValue"
            title="Сменить статус диалога?"
            :message="statusImpactRoutes.length
                ? `${statusImpactRoutes.length} маршрут(ов) будут переведены в черновик.`
                : 'Подтвердите смену статуса.'"
            @confirm="applyStatus(pendingStatusValue)"
            @cancel="cancelStatusChange"
        />
    </AuthenticatedLayout>
</template>

<style scoped>
.flow-layout {
    display: flex;
    flex: 1;
    overflow: hidden;
    height: 100%;
}

.fl-toolbar {
    display: flex;
    align-items: center;
    gap: 6px;
    width: 100%;
}
.fl-bcr {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 11px;
    color: var(--ink-3);
    flex-shrink: 0;
}
.fl-bcr a {
    color: var(--blue);
    text-decoration: none;
}
.fl-bcr a:hover {
    text-decoration: underline;
}
.fl-bcr .sep {
    color: var(--bdr-d);
}
.save-state {
    font-size: 11px;
    color: var(--ink-3);
    min-width: 80px;
    display: inline-block;
    text-align: right;
}
</style>

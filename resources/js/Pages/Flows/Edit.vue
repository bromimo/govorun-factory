<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import KeyboardHints from '@/Components/Flows/KeyboardHints.vue';
import FlowCanvas from '@/Components/Flows/FlowCanvas.vue';
import NodePalette from '@/Components/Flows/NodePalette.vue';
import NodeProperties from '@/Components/Flows/NodeProperties.vue';
import EdgeProperties from '@/Components/Flows/EdgeProperties.vue';
import ErButton from '@/Components/Ui/ErButton.vue';

const props = defineProps({
    bot: Object,
    flow: Object,
    can: Object,
});

const canvasRef = ref(null);
const saving = ref(false);
const saved = ref(false);
const description = ref(props.flow.description ?? '');
const panelWidth = ref(360);
const resizing = ref(false);

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

function onNodeDataUpdated(nodeId, newData) {
    canvasRef.value?.setNodeData(nodeId, newData);
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
    saving.value = true;
    saved.value = false;

    const graph = canvasRef.value.getGraph();

    router.put(route('bot-flows.update', [props.bot.id, props.flow.id]), {
        name: props.flow.name,
        description: description.value,
        graph,
    }, {
        preserveState: true,
        onSuccess: () => { saved.value = true; setTimeout(() => saved.value = false, 2000); },
        onFinish: () => { saving.value = false; },
    });
}

onMounted(() => document.body.classList.add('overflow-hidden'));
onBeforeUnmount(() => document.body.classList.remove('overflow-hidden'));

function fitView() {
    canvasRef.value?.doFitView();
}

function autoLayout() {
    canvasRef.value?.autoLayout();
}
</script>

<template>
    <Head :title="`Flow: ${flow.name}`" />
    <AuthenticatedLayout :flush="true">
        <template #subbar>
            <div class="fl-toolbar">
                <a :href="route('bots.edit', bot.id)" class="fl-back">← {{ bot.name }}</a>
                <span class="fl-sep">/</span>
                <span class="fl-title">{{ flow.name }}</span>
                <div style="flex: 1;" />
                <span v-if="saved" class="fl-saved">Сохранено</span>
                <ErButton v-if="can.update" size="sm" @click="autoLayout">Авто</ErButton>
                <ErButton size="sm" @click="fitView">Фит</ErButton>
                <a :href="route('bots.edit', bot.id)" class="fl-exit-btn">Выйти</a>
                <ErButton v-if="can.update" variant="primary" size="sm" :disabled="saving" @click="save">
                    {{ saving ? 'Сохранение…' : 'Сохранить' }}
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
.fl-back {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 12px;
    color: var(--ink-3);
    text-decoration: none;
    padding: 0 4px;
    flex-shrink: 0;
}
.fl-back:hover { color: var(--ink); }
.fl-sep { color: var(--bdr-d); font-size: 14px; padding: 0 2px; }
.fl-title { font-size: 12px; font-weight: 600; color: var(--ink); }
.fl-saved { font-size: 11px; color: var(--green-d, #2d6a2d); padding: 0 6px; }
.fl-exit-btn {
    display: flex;
    align-items: center;
    height: 22px;
    padding: 0 8px;
    font-size: 11px;
    font-family: var(--font);
    color: var(--ink-2);
    background: var(--surface);
    border: 1px solid var(--bdr-d);
    border-radius: var(--r-sm);
    text-decoration: none;
    cursor: pointer;
    white-space: nowrap;
}
.fl-exit-btn:hover { background: var(--surface-2); color: var(--ink); }
</style>

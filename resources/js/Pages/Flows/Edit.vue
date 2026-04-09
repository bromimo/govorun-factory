<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import KeyboardHints from '@/Components/Flows/KeyboardHints.vue';
import FlowCanvas from '@/Components/Flows/FlowCanvas.vue';
import NodePalette from '@/Components/Flows/NodePalette.vue';
import NodeProperties from '@/Components/Flows/NodeProperties.vue';
import EdgeProperties from '@/Components/Flows/EdgeProperties.vue';

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

const botValidationMessages = computed(() => props.bot.config?.validation_messages ?? {});

const siblingLabels = computed(() => {
    const edge = selectedEdge.value;
    if (!edge || !canvasRef.value) return [];
    return canvasRef.value.getOutgoingEdgeLabels(edge.source, edge.id);
});

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
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <a :href="route('bots.edit', bot.id)" class="text-sm text-gray-500 hover:text-gray-700">&larr; {{ bot.name }}</a>
                    <span class="text-gray-300">/</span>
                    <div>
                        <h2 class="text-xl font-semibold text-gray-800">{{ flow.name }}</h2>
                        <input v-if="can.update" v-model="description" type="text"
                            class="mt-0.5 w-full border-0 border-b border-transparent bg-transparent px-0 py-0 text-xs text-gray-500 placeholder-gray-400 focus:border-gray-300 focus:ring-0"
                            placeholder="Добавить описание..." />
                        <p v-else-if="description" class="text-xs text-gray-500">{{ description }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button v-if="can.update" @click="autoLayout" class="rounded-md border border-gray-300 px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-50">
                        Авто
                    </button>
                    <button @click="fitView" class="rounded-md border border-gray-300 px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-50">
                        Fit
                    </button>
                    <button v-if="can.update" @click="save" :disabled="saving"
                        class="rounded-md bg-indigo-600 px-4 py-1.5 text-sm font-medium text-white hover:bg-indigo-500 disabled:opacity-50">
                        {{ saving ? 'Сохранение...' : 'Сохранить' }}
                    </button>
                    <span v-if="saved" class="text-sm text-green-600">Сохранено</span>
                </div>
            </div>
        </template>

        <div class="relative flex h-[calc(100vh-10rem)] overflow-hidden">
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
                    :bot-validation-messages="botValidationMessages"
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
                    class="flex-1 min-w-0"
                    @update="onEdgeLabelUpdated"
                    @clear-waypoints="onClearWaypoints"
                    @close="canvasRef.selectedEdge = null"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, nextTick, onMounted, onBeforeUnmount } from 'vue';
import { VueFlow, useVueFlow, MarkerType } from '@vue-flow/core';
import { Background } from '@vue-flow/background';
import { Controls } from '@vue-flow/controls';
import FlowMinimap from './FlowMinimap.vue';
import dagre from '@dagrejs/dagre';
import { useFlowDragDrop } from './useFlowDragDrop.js';
import AskNode from './nodes/AskNode.vue';
import ReplyNode from './nodes/ReplyNode.vue';
import SaveStateNode from './nodes/SaveStateNode.vue';
import ConditionNode from './nodes/ConditionNode.vue';
import ApiCallNode from './nodes/ApiCallNode.vue';
import OnCompleteNode from './nodes/OnCompleteNode.vue';
import OnCancelNode from './nodes/OnCancelNode.vue';
import StartNode from './nodes/StartNode.vue';
import EditableEdge from './edges/EditableEdge.vue';

const props = defineProps({
    initialNodes: { type: Array, default: () => [] },
    initialEdges: { type: Array, default: () => [] },
    initialViewport: { type: Object, default: null },
});

const nodes = ref(props.initialNodes.map(n => n.type === 'start' ? { ...n, deletable: false } : n));
const arrowMarker = { type: MarkerType.ArrowClosed, width: 20, height: 20 };
const edgeDefaults = { markerEnd: arrowMarker, interactionWidth: 20, updatable: 'target', type: 'editable' };
// До исправления getGraph sourceHandle не сохранялся; восстанавливаем из id ребра.
// Формат id: vueflow__edge-{source}{sourceHandle}-{target}
function inferSourceHandle(edgeId, source) {
    const prefix = `vueflow__edge-${source}`;
    if (!edgeId.startsWith(prefix)) return null;
    const afterSource = edgeId.slice(prefix.length);
    const dashIdx = afterSource.indexOf('-');
    if (dashIdx <= 0) return null;
    return afterSource.slice(0, dashIdx);
}

const apiCallNodeIds = new Set(props.initialNodes.filter(n => n.type === 'api_call').map(n => n.id));
const edges = ref(props.initialEdges.map(e => {
    const edge = { ...edgeDefaults, ...e, data: e.data || {} };
    if (apiCallNodeIds.has(edge.source) && !edge.sourceHandle) {
        edge.sourceHandle = inferSourceHandle(edge.id, edge.source) ?? 'default';
    }
    return edge;
}));

const selectedNode = ref(null);
const selectedEdge = ref(null);

const { onConnect, addEdges, addNodes, onEdgeUpdate, onNodeClick, onEdgeClick, onPaneClick, toObject, fitView, updateNodeData, removeEdges, updateNodeInternals, getNodes, getEdges, onNodesChange } = useVueFlow();

onNodesChange((changes) => {
    for (const change of changes) {
        if (change.type === 'remove') {
            if (getNodes.value.find(n => n.id === change.id)?.type === 'start') {
                return changes.filter(c => c.id !== change.id);
            }
            if (selectedNode.value?.id === change.id) {
                selectedNode.value = null;
            }
        }
        if (change.type === 'select') {
            if (change.selected) {
                const node = getNodes.value.find(n => n.id === change.id);
                if (node && node.type !== 'start') {
                    selectedEdge.value = null;
                    selectedNode.value = { id: node.id, type: node.type, data: node.data };
                }
            } else if (selectedNode.value?.id === change.id) {
                selectedNode.value = null;
            }
        }
    }
});

function isValidConnection(connection) {
    if (connection.source === connection.target) return false;

    const sourceNode = getNodes.value.find(n => n.id === connection.source);
    if (!sourceNode) return false;

    if (sourceNode.type !== 'condition') {
        const hasOutgoing = getEdges.value.some(
            e => e.source === connection.source && e.sourceHandle === connection.sourceHandle,
        );
        if (hasOutgoing) return false;
    }

    return true;
}
const { onDragOver, onDrop } = useFlowDragDrop();

onConnect((params) => {
    addEdges({ ...edgeDefaults, ...params, label: '', data: {} });
});

onEdgeUpdate(({ edge, connection }) => {
    const existing = getEdges.value.find(e => e.id === edge.id);
    if (existing) {
        existing.source = connection.source;
        existing.target = connection.target;
        existing.sourceHandle = connection.sourceHandle;
        existing.targetHandle = connection.targetHandle;
    }
});

onNodeClick(({ node }) => {
    selectedEdge.value = null;
    selectedNode.value = node.type === 'start' ? null : { id: node.id, type: node.type, data: node.data };
});

onEdgeClick(({ edge }) => {
    const sourceNode = getNodes.value.find(n => n.id === edge.source);
    selectedEdge.value = {
        id: edge.id,
        source: edge.source,
        target: edge.target,
        label: edge.label,
        sourceIsCondition: sourceNode?.type === 'condition',
    };
    selectedNode.value = null;
});

onPaneClick(() => {
    selectedNode.value = null;
    selectedEdge.value = null;
});

function getGraph() {
    const obj = toObject();
    return {
        viewport: obj.viewport,
        nodes: obj.nodes.map(n => {
            const data = { ...n.data };
            if (Array.isArray(data.validation) && data.validation.length === 0) {
                delete data.validation;
            }
            return { id: n.id, type: n.type, data, position: n.position };
        }),
        edges: obj.edges.map(e => ({
            id: e.id,
            source: e.source,
            target: e.target,
            sourceHandle: e.sourceHandle || undefined,
            label: e.label || undefined,
            data: e.data?.waypoints?.length ? { waypoints: e.data.waypoints } : undefined,
        })),
    };
}

function doFitView() {
    fitView({ padding: 0.2 });
}

function setNodeData(nodeId, data) {
    const node = getNodes.value.find(n => n.id === nodeId);
    if (node?.type === 'api_call' && node.data.on_error === 'branch' && data.on_error !== 'branch') {
        const stale = getEdges.value
            .filter(e => e.source === nodeId && e.sourceHandle === 'on_error')
            .map(e => e.id);
        if (stale.length) removeEdges(stale);
    }
    updateNodeData(nodeId, data);
    if (node?.type === 'api_call') {
        nextTick(() => updateNodeInternals(nodeId));
    }
}

function getAllNodeIds() {
    return getNodes.value.map(n => n.id);
}

function getAllNodes() {
    return getNodes.value.map(n => ({ id: n.id, type: n.type }));
}

function renameNode(oldId, newId) {
    const allNodes = getNodes.value;
    const node = allNodes.find(n => n.id === oldId);
    if (!node) return false;
    if (allNodes.some(n => n.id === newId)) return false;

    node.id = newId;
    getEdges.value.forEach(e => {
        if (e.source === oldId) e.source = newId;
        if (e.target === oldId) e.target = newId;
    });
    return true;
}

function setEdgeLabel(edgeId, label) {
    const edge = getEdges.value.find(e => e.id === edgeId);
    if (edge) edge.label = label;
}

function clearEdgeWaypoints(edgeId) {
    const edge = getEdges.value.find(e => e.id === edgeId);
    if (edge?.data) edge.data.waypoints = [];
}

function getOutgoingEdgeLabels(sourceId, excludeEdgeId) {
    return getEdges.value
        .filter(e => e.source === sourceId && e.id !== excludeEdgeId && e.label)
        .map(e => e.label);
}

function getNodeStateKeys(node) {
    if (Array.isArray(node.data?.variables)) return node.data.variables.filter(v => v.key).map(v => v.key);
    if (node.data?.key) return [node.data.key];
    return [];
}

function getAllStateKeys() {
    return getNodes.value
        .filter(n => n.type === 'save_state')
        .flatMap(getNodeStateKeys);
}

/** Forward dataflow по ключам состояния с кастомной «перегородкой» (intersection или union). */
function computeStateKeysOnEntry(combiner) {
    const nodes = getNodes.value;
    const edges = getEdges.value;

    const incoming = new Map();
    for (const n of nodes) incoming.set(n.id, []);
    for (const e of edges) {
        if (incoming.has(e.target)) incoming.get(e.target).push(e.source);
    }

    const nodeById = new Map(nodes.map(n => [n.id, n]));
    const declaresOf = id => {
        const n = nodeById.get(id);
        return n?.type === 'save_state' ? getNodeStateKeys(n) : [];
    };

    const onEntry = new Map();
    for (const n of nodes) onEntry.set(n.id, combiner.seed(incoming.get(n.id).length === 0));

    let changed = true;
    let iterations = 0;
    const maxIterations = nodes.length + 2;
    while (changed && iterations++ < maxIterations) {
        changed = false;
        for (const n of nodes) {
            const preds = incoming.get(n.id);
            if (preds.length === 0) continue;

            let result = null;
            for (const p of preds) {
                const pOnExit = new Set(onEntry.get(p));
                for (const k of declaresOf(p)) pOnExit.add(k);
                result = result === null ? pOnExit : combiner.combine(result, pOnExit);
            }

            const current = onEntry.get(n.id);
            if (current.size !== result.size || [...result].some(k => !current.has(k))) {
                onEntry.set(n.id, result);
                changed = true;
            }
        }
    }

    return onEntry;
}

/** Ключи, гарантированно инициализированные к моменту входа в ноду (пересечение по путям). */
function getDeclaredStateKeysBefore(nodeId) {
    const allKeys = new Set(getAllStateKeys());
    const onEntry = computeStateKeysOnEntry({
        seed: isStart => isStart ? new Set() : new Set(allKeys),
        combine: (a, b) => {
            const inter = new Set();
            for (const k of a) if (b.has(k)) inter.add(k);
            return inter;
        },
    });
    return [...(onEntry.get(nodeId) ?? new Set())];
}

/** Ключи, инициализированные хотя бы на одном пути к ноде (объединение по путям). */
function getPossiblyDeclaredStateKeysBefore(nodeId) {
    const onEntry = computeStateKeysOnEntry({
        seed: () => new Set(),
        combine: (a, b) => {
            const union = new Set(a);
            for (const k of b) union.add(k);
            return union;
        },
    });
    return [...(onEntry.get(nodeId) ?? new Set())];
}

let clipboard = null;
let pasteCount = 0;

function generateId(type) {
    const existing = getNodes.value.map(n => n.id);
    let i = existing.length + 1;
    let id;
    do { id = `${type}_${i++}`; } while (existing.includes(id));
    return id;
}

function handleKeydown(e) {
    const tag = e.target.tagName;
    if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') return;
    if (!e.ctrlKey && !e.metaKey) return;

    if (e.key === 'c' || e.key === 'с' || e.code === 'KeyC') {
        e.preventDefault();
        const selected = getNodes.value.filter(n => n.selected && n.type !== 'start');
        if (!selected.length) return;

        const selectedIds = new Set(selected.map(n => n.id));
        const innerEdges = getEdges.value.filter(edge => selectedIds.has(edge.source) && selectedIds.has(edge.target));

        clipboard = {
            nodes: selected.map(n => ({ type: n.type, data: JSON.parse(JSON.stringify(n.data)), position: { ...n.position } })),
            edges: innerEdges.map(edge => ({ sourceIndex: selected.findIndex(n => n.id === edge.source), targetIndex: selected.findIndex(n => n.id === edge.target), label: edge.label, data: edge.data ? JSON.parse(JSON.stringify(edge.data)) : {} })),
        };
        pasteCount = 0;
    }

    if ((e.key === 'v' || e.key === 'м' || e.code === 'KeyV') && clipboard) {
        e.preventDefault();
        pasteCount++;
        const offset = 40 * pasteCount;
        const idMap = [];

        const newNodes = clipboard.nodes.map(n => {
            const id = generateId(n.type);
            idMap.push(id);
            return {
                id,
                type: n.type,
                data: JSON.parse(JSON.stringify(n.data)),
                position: { x: n.position.x + offset, y: n.position.y + offset },
            };
        });

        const newEdges = clipboard.edges
            .filter(edge => edge.sourceIndex >= 0 && edge.targetIndex >= 0)
            .map(edge => ({
                ...edgeDefaults,
                id: `e${idMap[edge.sourceIndex]}-${idMap[edge.targetIndex]}`,
                source: idMap[edge.sourceIndex],
                target: idMap[edge.targetIndex],
                label: edge.label || '',
                data: edge.data ? JSON.parse(JSON.stringify(edge.data)) : {},
            }));

        getNodes.value.forEach(n => { n.selected = false; });
        getEdges.value.forEach(edge => { edge.selected = false; });

        newNodes.forEach(n => { n.selected = true; });
        addNodes(newNodes);
        if (newEdges.length) addEdges(newEdges);
    }
}

onMounted(() => document.addEventListener('keydown', handleKeydown));
onBeforeUnmount(() => document.removeEventListener('keydown', handleKeydown));

function autoLayout() {
    const g = new dagre.graphlib.Graph();
    g.setDefaultEdgeLabel(() => ({}));
    g.setGraph({ rankdir: 'TB', nodesep: 60, ranksep: 80, edgesep: 30, acyclicer: 'greedy' });

    getNodes.value.forEach(n => {
        g.setNode(n.id, { width: n.dimensions?.width || 180, height: n.dimensions?.height || 60 });
    });

    const nodeRank = {};
    const visited = new Set();
    const edgeList = getEdges.value;

    function assignRank(id, rank) {
        if (visited.has(id)) return;
        visited.add(id);
        nodeRank[id] = rank;
        edgeList.filter(e => e.source === id).forEach(e => assignRank(e.target, rank + 1));
    }
    const targets = new Set(edgeList.map(e => e.target));
    const roots = getNodes.value.filter(n => !targets.has(n.id));
    roots.forEach(r => assignRank(r.id, 0));

    edgeList.forEach(e => {
        const isBackEdge = (nodeRank[e.source] ?? 0) >= (nodeRank[e.target] ?? 0);
        g.setEdge(e.source, e.target, { weight: isBackEdge ? 1 : 2, minlen: isBackEdge ? 2 : 1 });
    });

    dagre.layout(g);

    getNodes.value.forEach(n => {
        const pos = g.node(n.id);
        n.position = { x: pos.x - (n.dimensions?.width || 180) / 2, y: pos.y - (n.dimensions?.height || 60) / 2 };
    });

    setTimeout(() => fitView({ padding: 0.2 }), 50);
}

defineExpose({ getGraph, doFitView, autoLayout, setNodeData, getAllNodeIds, getAllNodes, renameNode, setEdgeLabel, getOutgoingEdgeLabels, getAllStateKeys, getDeclaredStateKeysBefore, getPossiblyDeclaredStateKeysBefore, clearEdgeWaypoints, selectedNode, selectedEdge });
</script>

<template>
    <VueFlow
        :nodes="nodes"
        :edges="edges"
        :min-zoom="0.3"
        :max-zoom="2"
        :delete-key-code="['Backspace', 'Delete']"
        :is-valid-connection="isValidConnection"
        :default-viewport="initialViewport ?? undefined"
        :fit-view-on-init="!initialViewport"
        connect-on-click
        class="flex-1"
        @dragover="onDragOver"
        @drop="onDrop"
    >
        <template #node-start="p"><StartNode v-bind="p" /></template>
        <template #node-ask="p"><AskNode v-bind="p" /></template>
        <template #node-reply="p"><ReplyNode v-bind="p" /></template>
        <template #node-save_state="p"><SaveStateNode v-bind="p" /></template>
        <template #node-condition="p"><ConditionNode v-bind="p" /></template>
        <template #node-api_call="p"><ApiCallNode v-bind="p" /></template>
        <template #node-on_complete="p"><OnCompleteNode v-bind="p" /></template>
        <template #node-on_cancel="p"><OnCancelNode v-bind="p" /></template>

        <template #edge-editable="edgeProps"><EditableEdge v-bind="edgeProps" /></template>

        <Background :gap="16" />
        <Controls />
        <FlowMinimap />
    </VueFlow>
</template>

<style scoped>
:deep(.vue-flow__background) {
    background-color: #eaedf0;
}
</style>

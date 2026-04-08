<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { VueFlow, useVueFlow, MarkerType } from '@vue-flow/core';
import { Background } from '@vue-flow/background';
import { Controls } from '@vue-flow/controls';
import dagre from '@dagrejs/dagre';
import { useFlowDragDrop } from './useFlowDragDrop.js';
import AskTextNode from './nodes/AskTextNode.vue';
import AskKeyboardNode from './nodes/AskKeyboardNode.vue';
import ReplyTextNode from './nodes/ReplyTextNode.vue';
import ReplyKeyboardNode from './nodes/ReplyKeyboardNode.vue';
import ReplyMediaNode from './nodes/ReplyMediaNode.vue';
import SaveStateNode from './nodes/SaveStateNode.vue';
import ConditionNode from './nodes/ConditionNode.vue';
import ApiCallNode from './nodes/ApiCallNode.vue';
import OnCompleteNode from './nodes/OnCompleteNode.vue';
import OnCancelNode from './nodes/OnCancelNode.vue';
import EditableEdge from './edges/EditableEdge.vue';

const props = defineProps({
    initialNodes: { type: Array, default: () => [] },
    initialEdges: { type: Array, default: () => [] },
});

const nodes = ref([...props.initialNodes]);
const arrowMarker = { type: MarkerType.ArrowClosed, width: 20, height: 20 };
const edgeDefaults = { markerEnd: arrowMarker, interactionWidth: 20, updatable: 'target', type: 'editable' };
const edges = ref(props.initialEdges.map(e => ({ ...edgeDefaults, ...e, data: e.data || {} })));

const selectedNode = ref(null);
const selectedEdge = ref(null);

const { onConnect, addEdges, addNodes, onEdgeUpdate, onNodeClick, onEdgeClick, onPaneClick, toObject, fitView, updateNodeData, getNodes, getEdges } = useVueFlow();

function isValidConnection(connection) {
    if (connection.source === connection.target) return false;

    const sourceNode = getNodes.value.find(n => n.id === connection.source);
    if (!sourceNode) return false;

    if (sourceNode.type !== 'condition') {
        const hasOutgoing = getEdges.value.some(e => e.source === connection.source);
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
    selectedNode.value = { id: node.id, type: node.type, data: node.data };
    selectedEdge.value = null;
});

onEdgeClick(({ edge }) => {
    selectedEdge.value = { id: edge.id, source: edge.source, target: edge.target, label: edge.label };
    selectedNode.value = null;
});

onPaneClick(() => {
    selectedNode.value = null;
    selectedEdge.value = null;
});

function getGraph() {
    const obj = toObject();
    return {
        nodes: obj.nodes.map(n => ({
            id: n.id,
            type: n.type,
            data: n.data,
            position: n.position,
        })),
        edges: obj.edges.map(e => ({
            id: e.id,
            source: e.source,
            target: e.target,
            label: e.label || undefined,
            data: e.data?.waypoints?.length ? { waypoints: e.data.waypoints } : undefined,
        })),
    };
}

function doFitView() {
    fitView({ padding: 0.2 });
}

function setNodeData(nodeId, data) {
    updateNodeData(nodeId, data);
}

function getAllNodeIds() {
    return getNodes.value.map(n => n.id);
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

function getAncestorIds(nodeId) {
    const edgeList = getEdges.value;
    const visited = new Set();
    const queue = [nodeId];
    while (queue.length) {
        const current = queue.shift();
        for (const e of edgeList) {
            if (e.target === current && !visited.has(e.source)) {
                visited.add(e.source);
                queue.push(e.source);
            }
        }
    }
    return visited;
}

function getAllStateKeys() {
    return getNodes.value
        .filter(n => n.type === 'save_state' && n.data?.key)
        .map(n => n.data.key);
}

function getDeclaredStateKeysBefore(nodeId) {
    const ancestors = getAncestorIds(nodeId);
    return getNodes.value
        .filter(n => ancestors.has(n.id) && n.type === 'save_state' && n.data?.key)
        .map(n => n.data.key);
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
        const selected = getNodes.value.filter(n => n.selected);
        if (!selected.length) return;

        const selectedIds = new Set(selected.map(n => n.id));
        const innerEdges = getEdges.value.filter(edge => selectedIds.has(edge.source) && selectedIds.has(edge.target));

        clipboard = {
            nodes: selected.map(n => ({ type: n.type, data: JSON.parse(JSON.stringify(n.data)), position: { ...n.position } })),
            edges: innerEdges.map(edge => ({ sourceIndex: selected.findIndex(n => n.id === edge.source), targetIndex: selected.findIndex(n => n.id === edge.target), label: edge.label })),
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

defineExpose({ getGraph, doFitView, autoLayout, setNodeData, getAllNodeIds, renameNode, setEdgeLabel, getOutgoingEdgeLabels, getAllStateKeys, getDeclaredStateKeysBefore, clearEdgeWaypoints, selectedNode, selectedEdge });
</script>

<template>
    <VueFlow
        :nodes="nodes"
        :edges="edges"
        :min-zoom="0.3"
        :max-zoom="2"
        :delete-key-code="['Backspace', 'Delete']"
        :is-valid-connection="isValidConnection"
        :fit-view-on-init="true"
        connect-on-click
        class="flex-1"
        @dragover="onDragOver"
        @drop="onDrop"
    >
        <template #node-ask_text="p"><AskTextNode v-bind="p" /></template>
        <template #node-ask_keyboard="p"><AskKeyboardNode v-bind="p" /></template>
        <template #node-reply_text="p"><ReplyTextNode v-bind="p" /></template>
        <template #node-reply_keyboard="p"><ReplyKeyboardNode v-bind="p" /></template>
        <template #node-reply_media="p"><ReplyMediaNode v-bind="p" /></template>
        <template #node-save_state="p"><SaveStateNode v-bind="p" /></template>
        <template #node-condition="p"><ConditionNode v-bind="p" /></template>
        <template #node-api_call="p"><ApiCallNode v-bind="p" /></template>
        <template #node-on_complete="p"><OnCompleteNode v-bind="p" /></template>
        <template #node-on_cancel="p"><OnCancelNode v-bind="p" /></template>

        <template #edge-editable="edgeProps"><EditableEdge v-bind="edgeProps" /></template>

        <Background :gap="16" />
        <Controls />
    </VueFlow>
</template>

<script setup>
import { ref } from 'vue';
import { VueFlow, useVueFlow, MarkerType } from '@vue-flow/core';
import { Background } from '@vue-flow/background';
import { Controls } from '@vue-flow/controls';
import { useFlowDragDrop } from './useFlowDragDrop.js';
import AskTextNode from './nodes/AskTextNode.vue';
import AskKeyboardNode from './nodes/AskKeyboardNode.vue';
import ReplyTextNode from './nodes/ReplyTextNode.vue';
import ReplyKeyboardNode from './nodes/ReplyKeyboardNode.vue';
import SaveStateNode from './nodes/SaveStateNode.vue';
import ConditionNode from './nodes/ConditionNode.vue';
import ApiCallNode from './nodes/ApiCallNode.vue';
import OnCompleteNode from './nodes/OnCompleteNode.vue';
import OnCancelNode from './nodes/OnCancelNode.vue';

const props = defineProps({
    initialNodes: { type: Array, default: () => [] },
    initialEdges: { type: Array, default: () => [] },
});

const nodes = ref([...props.initialNodes]);
const arrowMarker = { type: MarkerType.ArrowClosed, width: 20, height: 20 };
const edgeDefaults = { markerEnd: arrowMarker, interactionWidth: 20 };
const edges = ref(props.initialEdges.map(e => ({ ...edgeDefaults, ...e })));

const selectedNode = ref(null);
const selectedEdge = ref(null);

const { onConnect, addEdges, onNodeClick, onEdgeClick, onPaneClick, toObject, fitView, updateNodeData, getNodes, getEdges } = useVueFlow();
const { onDragOver, onDrop } = useFlowDragDrop();

onConnect((params) => {
    addEdges({ ...edgeDefaults, ...params, label: '' });
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

function getOutgoingEdgeLabels(sourceId, excludeEdgeId) {
    return getEdges.value
        .filter(e => e.source === sourceId && e.id !== excludeEdgeId && e.label)
        .map(e => e.label);
}

defineExpose({ getGraph, doFitView, setNodeData, getAllNodeIds, renameNode, setEdgeLabel, getOutgoingEdgeLabels, selectedNode, selectedEdge });
</script>

<template>
    <VueFlow
        :nodes="nodes"
        :edges="edges"
        :default-viewport="{ zoom: 1 }"
        :min-zoom="0.3"
        :max-zoom="2"
        :delete-key-code="['Backspace', 'Delete']"
        class="flex-1"
        @dragover="onDragOver"
        @drop="onDrop"
    >
        <template #node-ask_text="p"><AskTextNode v-bind="p" /></template>
        <template #node-ask_keyboard="p"><AskKeyboardNode v-bind="p" /></template>
        <template #node-reply_text="p"><ReplyTextNode v-bind="p" /></template>
        <template #node-reply_keyboard="p"><ReplyKeyboardNode v-bind="p" /></template>
        <template #node-save_state="p"><SaveStateNode v-bind="p" /></template>
        <template #node-condition="p"><ConditionNode v-bind="p" /></template>
        <template #node-api_call="p"><ApiCallNode v-bind="p" /></template>
        <template #node-on_complete="p"><OnCompleteNode v-bind="p" /></template>
        <template #node-on_cancel="p"><OnCancelNode v-bind="p" /></template>

        <Background :gap="16" />
        <Controls />
    </VueFlow>
</template>

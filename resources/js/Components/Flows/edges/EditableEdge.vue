<script setup>
import { ref, computed } from 'vue';
import { EdgeLabelRenderer, useVueFlow } from '@vue-flow/core';
import { buildBezierPath, findInsertIndex } from './bezierPath.js';

defineOptions({ inheritAttrs: false });

const props = defineProps({
    id: String,
    source: String,
    target: String,
    sourceX: Number,
    sourceY: Number,
    targetX: Number,
    targetY: Number,
    sourcePosition: String,
    targetPosition: String,
    label: [String, Object],
    style: Object,
    markerEnd: String,
    markerStart: String,
    selected: Boolean,
    data: { type: Object, default: () => ({}) },
});

const { project, getEdges } = useVueFlow();

const draggingIndex = ref(null);

const waypoints = computed(() => props.data?.waypoints ?? []);

const svgPath = computed(() =>
    buildBezierPath(props.sourceX, props.sourceY, props.targetX, props.targetY, waypoints.value)
);

const midpoint = computed(() => {
    const pts = [
        { x: props.sourceX, y: props.sourceY },
        ...waypoints.value,
        { x: props.targetX, y: props.targetY },
    ];
    const mid = Math.floor((pts.length - 1) / 2);
    return {
        x: (pts[mid].x + pts[mid + 1].x) / 2,
        y: (pts[mid].y + pts[mid + 1].y) / 2,
    };
});

function getEdgeData() {
    const edge = getEdges.value.find(e => e.id === props.id);
    if (!edge) return null;
    if (!edge.data) edge.data = {};
    if (!edge.data.waypoints) edge.data.waypoints = [];
    return edge;
}

function onInteractionDblClick(event) {
    event.stopPropagation();
    const flowPos = project({ x: event.clientX, y: event.clientY });
    const edge = getEdgeData();
    if (!edge) return;

    const idx = findInsertIndex(
        props.sourceX, props.sourceY,
        props.targetX, props.targetY,
        edge.data.waypoints,
        flowPos
    );
    edge.data.waypoints.splice(idx, 0, { x: flowPos.x, y: flowPos.y });
}

function onControlDblClick(event, index) {
    event.stopPropagation();
    const edge = getEdgeData();
    if (!edge) return;
    edge.data.waypoints.splice(index, 1);
}

function onControlMouseDown(event, index) {
    event.stopPropagation();
    event.preventDefault();
    draggingIndex.value = index;

    function onMouseMove(e) {
        if (draggingIndex.value === null) return;
        const flowPos = project({ x: e.clientX, y: e.clientY });
        const edge = getEdgeData();
        if (!edge) return;
        edge.data.waypoints[draggingIndex.value] = { x: flowPos.x, y: flowPos.y };
    }

    function onMouseUp() {
        draggingIndex.value = null;
        window.removeEventListener('mousemove', onMouseMove);
        window.removeEventListener('mouseup', onMouseUp);
    }

    window.addEventListener('mousemove', onMouseMove);
    window.addEventListener('mouseup', onMouseUp);
}
</script>

<template>
    <g>
        <!-- Невидимый широкий путь для обработки кликов -->
        <path
            :d="svgPath"
            fill="none"
            stroke="transparent"
            stroke-width="20"
            style="cursor: pointer;"
            @dblclick="onInteractionDblClick"
        />

        <!-- Видимый путь ребра -->
        <path
            :id="id"
            :d="svgPath"
            fill="none"
            :stroke="selected ? '#6366f1' : '#94a3b8'"
            :stroke-width="selected ? 3 : 1.5"
            :marker-end="markerEnd"
            :marker-start="markerStart"
            :style="style"
            style="pointer-events: none;"
        />

        <!-- Управляющие точки (отображаются только при выделении) -->
        <template v-if="selected">
            <circle
                v-for="(wp, i) in waypoints"
                :key="i"
                :cx="wp.x"
                :cy="wp.y"
                r="5"
                fill="white"
                stroke="#6366f1"
                stroke-width="2"
                :class="draggingIndex === i ? 'cursor-grabbing' : 'cursor-grab'"
                @mousedown="onControlMouseDown($event, i)"
                @dblclick="onControlDblClick($event, i)"
            />
        </template>
    </g>

    <!-- Метка в середине ребра -->
    <EdgeLabelRenderer v-if="label">
        <div
            :style="{
                position: 'absolute',
                transform: `translate(-50%, -50%) translate(${midpoint.x}px, ${midpoint.y}px)`,
                pointerEvents: 'all',
            }"
            class="rounded bg-white px-1 py-0.5 text-[10px] text-gray-600 shadow-sm border border-gray-200 nodrag nopan"
        >
            {{ label }}
        </div>
    </EdgeLabelRenderer>
</template>

<script setup>
import { computed } from 'vue';
import { BaseEdge, EdgeLabelRenderer, useVueFlow } from '@vue-flow/core';
import { PathFindingEdge } from '@vue-flow/pathfinding-edge';

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
    labelStyle: Object,
    labelShowBg: Boolean,
    labelBgStyle: Object,
    labelBgPadding: Array,
    labelBgBorderRadius: Number,
    style: Object,
    markerEnd: String,
    markerStart: String,
    selected: Boolean,
    animated: Boolean,
});

const { getNodes } = useVueFlow();

const isBackEdge = computed(() => props.sourceY > props.targetY);

const backPath = computed(() => {
    const { sourceX, sourceY, targetX, targetY } = props;

    const pad = 40;
    let leftX = Infinity;
    getNodes.value.forEach(n => {
        const nx = n.computedPosition?.x ?? n.position?.x ?? 0;
        if (nx < leftX) leftX = nx;
    });
    leftX -= pad;

    const r = 15;
    const downY = sourceY + pad;
    const upY = targetY - pad;

    return `M${sourceX},${sourceY}`
        + ` L${sourceX},${downY - r}`
        + ` Q${sourceX},${downY} ${sourceX - r},${downY}`
        + ` L${leftX + r},${downY}`
        + ` Q${leftX},${downY} ${leftX},${downY - r}`
        + ` L${leftX},${upY + r}`
        + ` Q${leftX},${upY} ${leftX + r},${upY}`
        + ` L${targetX - r},${upY}`
        + ` Q${targetX},${upY} ${targetX},${upY + r}`
        + ` L${targetX},${targetY}`;
});

const backLabelPos = computed(() => {
    let leftX = Infinity;
    getNodes.value.forEach(n => {
        const nx = n.computedPosition?.x ?? n.position?.x ?? 0;
        if (nx < leftX) leftX = nx;
    });
    return { x: leftX - 40, y: (props.sourceY + props.targetY) / 2 };
});
</script>

<template>
    <PathFindingEdge v-if="!isBackEdge" v-bind="props" />

    <template v-else>
        <BaseEdge :id="id" :path="backPath" :style="style" :marker-end="markerEnd" :marker-start="markerStart" />
        <EdgeLabelRenderer v-if="label">
            <div :style="{ position: 'absolute', transform: `translate(-50%, -50%) translate(${backLabelPos.x}px, ${backLabelPos.y}px)`, pointerEvents: 'all' }"
                class="rounded bg-white px-1 py-0.5 text-[10px] text-gray-600 shadow-sm border border-gray-200 nodrag nopan">
                {{ label }}
            </div>
        </EdgeLabelRenderer>
    </template>
</template>

<script setup>
import { computed } from 'vue';
import { useVueFlow } from '@vue-flow/core';
import { NODE_TYPE_COLORS } from '@/Components/Blocks/blockTypes.js';

const { getNodes, viewport, dimensions } = useVueFlow();

const DEFAULT_COLOR = { stripe: '#9aa5b2', fill: '#eef1f4' };

function nodeColor(type) {
    return NODE_TYPE_COLORS[type] ?? DEFAULT_COLOR;
}

const graphBounds = computed(() => {
    const nodes = getNodes.value;
    if (!nodes.length) {
        return { x: -200, y: -150, w: 600, h: 450 };
    }
    const pad = 60;
    let x0 = Infinity, y0 = Infinity, x1 = -Infinity, y1 = -Infinity;
    for (const n of nodes) {
        const w = n.dimensions?.width ?? 180;
        const h = n.dimensions?.height ?? 60;
        x0 = Math.min(x0, n.position.x);
        y0 = Math.min(y0, n.position.y);
        x1 = Math.max(x1, n.position.x + w);
        y1 = Math.max(y1, n.position.y + h);
    }
    return { x: x0 - pad, y: y0 - pad, w: x1 - x0 + pad * 2, h: y1 - y0 + pad * 2 };
});

const viewRect = computed(() => {
    const { x, y, zoom } = viewport.value;
    const cw = dimensions.value.width || 800;
    const ch = dimensions.value.height || 600;
    return {
        x: -x / zoom,
        y: -y / zoom,
        w: cw / zoom,
        h: ch / zoom,
    };
});
</script>

<template>
    <div class="absolute bottom-2 right-2 overflow-hidden rounded-md border border-gray-200 bg-white shadow select-none pointer-events-none"
         style="z-index: 5; width: 200px; height: 140px;">
        <svg
            :viewBox="`${graphBounds.x} ${graphBounds.y} ${graphBounds.w} ${graphBounds.h}`"
            width="200" height="140"
            preserveAspectRatio="xMidYMid meet"
            style="display: block;"
        >
            <rect
                v-for="n in getNodes" :key="n.id"
                :x="n.position.x" :y="n.position.y"
                :width="n.dimensions?.width ?? 180"
                :height="n.dimensions?.height ?? 60"
                :fill="nodeColor(n.type).fill"
                :stroke="nodeColor(n.type).stripe"
                stroke-width="2"
                vector-effect="non-scaling-stroke"
                rx="4"
            />
            <rect
                :x="viewRect.x" :y="viewRect.y"
                :width="Math.max(viewRect.w, 1)" :height="Math.max(viewRect.h, 1)"
                fill="rgba(99, 102, 241, 0.08)"
                stroke="rgb(99, 102, 241)"
                stroke-width="2"
                vector-effect="non-scaling-stroke"
                rx="3"
            />
        </svg>
    </div>
</template>
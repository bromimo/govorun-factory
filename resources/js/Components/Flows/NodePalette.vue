<script setup>
import { flowNodeTypes, colorClasses } from '../Blocks/blockTypes.js';
import { useFlowDragDrop } from './useFlowDragDrop.js';

const { onDragStart } = useFlowDragDrop();
</script>

<template>
    <div class="border-r border-gray-200 bg-gray-50 p-3 overflow-y-auto">
        <h3 class="mb-3 text-xs font-bold uppercase tracking-wider text-gray-500">Узлы</h3>
        <div class="space-y-2">
            <div v-for="nt in flowNodeTypes" :key="nt.type"
                draggable="true"
                @dragstart="(e) => onDragStart(e, nt.type)"
                class="cursor-grab rounded-md border-2 px-3 py-2 text-xs font-medium select-none transition hover:shadow-sm"
                :class="[colorClasses[nt.color]?.border, colorClasses[nt.color]?.bg, colorClasses[nt.color]?.text]">
                {{ nt.label }}
            </div>
        </div>
    </div>
</template>

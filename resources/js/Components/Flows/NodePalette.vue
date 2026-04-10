<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { getFlowNodeTypesWithPlugins, colorClasses } from '../Blocks/blockTypes.js';
import { useFlowDragDrop } from './useFlowDragDrop.js';

const { onDragStart } = useFlowDragDrop();
const plugins = computed(() => usePage().props.plugins ?? []);
const nodeTypes = computed(() => getFlowNodeTypesWithPlugins(plugins.value));
</script>

<template>
    <div class="border-r border-gray-200 bg-gray-50 p-3 overflow-y-auto">
        <h3 class="mb-3 text-xs font-bold uppercase tracking-wider text-gray-500">Узлы</h3>
        <div class="space-y-2">
            <div v-for="nt in nodeTypes" :key="nt.type"
                draggable="true"
                @dragstart="(e) => onDragStart(e, nt.type)"
                class="cursor-grab rounded-md border-2 px-3 py-2 text-xs font-medium select-none transition hover:shadow-sm"
                :class="[colorClasses[nt.color]?.border, colorClasses[nt.color]?.bg, colorClasses[nt.color]?.text]">
                {{ nt.label }}
                <span v-if="nt.isPlugin" class="ml-1 text-gray-400">(plugin)</span>
            </div>
        </div>
    </div>
</template>

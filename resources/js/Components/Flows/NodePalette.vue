<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    MessageCircleQuestion,
    MessageSquare,
    Save,
    GitBranch,
    Globe,
    CheckCircle2,
    XCircle,
    Play,
    Plug,
} from 'lucide-vue-next';
import { getFlowNodeTypesWithPlugins, NODE_TYPE_COLORS } from '../Blocks/blockTypes.js';
import { useFlowDragDrop } from './useFlowDragDrop.js';

const { onDragStart } = useFlowDragDrop();
const plugins = computed(() => usePage().props.plugins ?? []);
const nodeTypes = computed(() => getFlowNodeTypesWithPlugins(plugins.value));

const iconMap = {
    ask:         MessageCircleQuestion,
    reply:       MessageSquare,
    save_state:  Save,
    condition:   GitBranch,
    api_call:    Globe,
    on_complete: CheckCircle2,
    on_cancel:   XCircle,
    start:       Play,
};

function getIcon(type) {
    return iconMap[type] ?? Plug;
}

function gradStyle(type) {
    const c = NODE_TYPE_COLORS[type];
    if (c?.gradFrom && c?.gradTo) {
        return { background: `linear-gradient(135deg, ${c.gradFrom} 0%, ${c.gradTo} 100%)` };
    }
    return { background: c?.stripe ?? '#9aa5b2' };
}
</script>

<template>
    <div class="palette">
        <div class="palette-h">Узлы</div>
        <div class="palette-list">
            <div v-for="nt in nodeTypes" :key="nt.type"
                draggable="true"
                @dragstart="(e) => onDragStart(e, nt.type)"
                class="palette-item select-none">
                <div class="item-icon" :style="gradStyle(nt.type)">
                    <component :is="getIcon(nt.type)" :size="16" color="white" />
                </div>
                <span class="item-label">
                    {{ nt.label }}
                    <span v-if="nt.isPlugin" class="plugin-badge">(plugin)</span>
                </span>
            </div>
        </div>
    </div>
</template>

<style scoped>
.palette {
    width: 200px;
    background: var(--surface);
    border-right: 1px solid var(--bdr);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    flex-shrink: 0;
}
.palette-h {
    padding: 6px 10px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .04em;
    color: var(--ink-3);
    background: linear-gradient(180deg, #f4f6f8 0%, #e8ecf0 100%);
    border-bottom: 1px solid var(--bdr);
    flex-shrink: 0;
}
.palette-list {
    flex: 1;
    overflow-y: auto;
    padding: 6px 8px;
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.palette-item {
    display: flex;
    align-items: stretch;
    cursor: grab;
    border-radius: var(--r-sm, 4px);
    overflow: hidden;
    border: 1px solid var(--bdr);
    height: 36px;
    transition: box-shadow .12s;
}
.palette-item:hover {
    box-shadow: var(--sh);
}
.item-icon {
    width: 44px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}
.item-label {
    flex: 1;
    display: flex;
    align-items: center;
    padding: 0 10px;
    font-size: 12px;
    color: var(--ink-2);
    background: var(--surface);
}
.plugin-badge {
    margin-left: 4px;
    color: var(--ink-3);
    font-size: 11px;
}
</style>
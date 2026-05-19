<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { getFlowNodeTypesWithPlugins } from '../Blocks/blockTypes.js';
import { useFlowDragDrop } from './useFlowDragDrop.js';

const { onDragStart } = useFlowDragDrop();
const plugins = computed(() => usePage().props.plugins ?? []);
const nodeTypes = computed(() => getFlowNodeTypesWithPlugins(plugins.value));

const nodeColors = {
    ask_text: '#2a5ca0', ask_keyboard: '#2a5ca0',
    reply_text: '#b03030', reply_keyboard: '#b03030', reply_media: '#b03030',
    save_state: '#3a7a3a', condition: '#c87020',
    api_call: '#3a72c4', on_complete: '#5a6878',
    on_cancel: '#5a6878', start: '#5a6878',
    ask: '#2a5ca0', reply: '#b03030',
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
                <div class="item-stripe" :style="{ background: nodeColors[nt.type] ?? '#9aa5b2' }"></div>
                {{ nt.label }}
                <span v-if="nt.isPlugin" class="plugin-badge">(plugin)</span>
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
.palette-list { flex: 1; overflow-y: auto; padding: 4px 0; }
.palette-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 5px 10px;
    cursor: grab;
    font-size: 12px;
    color: var(--ink-2);
    border-left: 3px solid transparent;
    transition: background .1s;
}
.palette-item:hover { background: var(--surface-2); }
.item-stripe {
    width: 3px;
    height: 18px;
    border-radius: 1px;
    flex-shrink: 0;
}
.plugin-badge {
    margin-left: 4px;
    color: var(--ink-3);
    font-size: 11px;
}
</style>
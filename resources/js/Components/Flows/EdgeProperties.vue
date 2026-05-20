<script setup>
import { ref, watch } from 'vue';
import {
    X,
    ArrowRight,
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
import { NODE_TYPE_COLORS } from '../Blocks/blockTypes.js';

const props = defineProps({
    edge: Object,
    canUpdate: Boolean,
    siblingLabels: { type: Array, default: () => [] },
    allNodes: { type: Array, default: () => [] },
});

const emit = defineEmits(['update', 'close', 'clear-waypoints']);

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

const FALLBACK_COLORS = { gradFrom: '#9aa5b2', gradTo: '#7a8a9a' };

function nodeColors(nodeId) {
    const node = props.allNodes.find(n => n.id === nodeId);
    return NODE_TYPE_COLORS[node?.type] ?? FALLBACK_COLORS;
}

function nodeIcon(nodeId) {
    const node = props.allNodes.find(n => n.id === nodeId);
    return iconMap[node?.type] ?? Plug;
}

function chipGrad(nodeId) {
    const c = nodeColors(nodeId);
    return `linear-gradient(135deg, ${c.gradFrom} 0%, ${c.gradTo} 100%)`;
}

const localLabel = ref(props.edge?.label ?? '');
const labelError = ref('');

watch(() => props.edge?.id, () => {
    localLabel.value = props.edge?.label ?? '';
    labelError.value = '';
});

function validateAndUpdateLabel() {
    const label = localLabel.value.trim();
    if (label && props.siblingLabels.includes(label)) {
        labelError.value = 'Такая метка уже есть на другой связи';
        return;
    }
    labelError.value = '';
    emit('update', props.edge.id, label);
}
</script>

<template>
    <div class="inspector">
        <div class="insp-h">
            <div class="insp-icon">
                <ArrowRight :size="18" color="white" />
            </div>
            <div class="insp-meta">
                <div class="insp-type">Связь</div>
                <div class="insp-id">{{ edge.source }} → {{ edge.target }}</div>
            </div>
            <button class="insp-close" @click="emit('close')" title="Закрыть">
                <X :size="14" />
            </button>
        </div>

        <div class="insp-body">
            <div class="mb-3">
                <label class="field-lbl">Маршрут</label>
                <div class="nodes-row">
                    <div class="node-chip">
                        <div class="node-chip-icon" :style="{ background: chipGrad(edge.source) }">
                            <component :is="nodeIcon(edge.source)" :size="13" color="white" />
                        </div>
                        <span class="node-chip-id">{{ edge.source }}</span>
                    </div>
                    <ArrowRight :size="14" class="edge-arrow" />
                    <div class="node-chip">
                        <div class="node-chip-icon" :style="{ background: chipGrad(edge.target) }">
                            <component :is="nodeIcon(edge.target)" :size="13" color="white" />
                        </div>
                        <span class="node-chip-id">{{ edge.target }}</span>
                    </div>
                </div>
            </div>

            <div class="divider" />

            <div v-if="edge.sourceIsCondition">
                <label class="field-lbl">Значение</label>
                <div v-if="canUpdate" class="mt-1">
                    <input
                        v-model="localLabel"
                        @blur="validateAndUpdateLabel"
                        @keydown.enter="validateAndUpdateLabel"
                        class="field-input"
                        :class="labelError ? 'field-input--error' : ''"
                        placeholder="yes, no, default..."
                    />
                    <p v-if="labelError" class="field-err">{{ labelError }}</p>
                </div>
                <p v-else class="field-mono">{{ edge.label || '—' }}</p>
            </div>

            <div v-if="canUpdate" class="mt-3">
                <button class="er-btn sm" @click="emit('clear-waypoints', edge.id)">
                    Сбросить маршрут
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.inspector {
    flex: 1;
    min-width: 0;
    background: var(--surface);
    border-left: 1px solid var(--bdr);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
.insp-h {
    display: flex;
    align-items: stretch;
    border-bottom: 1px solid var(--bdr);
    flex-shrink: 0;
}
.insp-icon {
    width: 56px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(160deg, #7a8a9a 0%, #5a6878 100%);
}
.insp-meta {
    flex: 1;
    min-width: 0;
    padding: 10px;
    background: #f0f3f5;
}
.insp-type {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: var(--ink-3);
    line-height: 1.2;
}
.insp-id {
    font-size: 12px;
    font-family: var(--mono, monospace);
    color: var(--ink-2);
    margin-top: 2px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.insp-close {
    flex-shrink: 0;
    width: 36px;
    background: #f0f3f5;
    border: none;
    border-left: 1px solid var(--bdr);
    cursor: pointer;
    color: var(--ink-3);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: color .1s;
}
.insp-close:hover { color: var(--red, #b03030); }

.insp-body {
    flex: 1;
    overflow-y: auto;
    padding: 12px;
}

.nodes-row {
    display: flex;
    align-items: center;
    gap: 4px;
    min-width: 0;
}
.node-chip {
    display: inline-flex;
    align-items: stretch;
    border: 1px solid var(--bdr);
    border-radius: var(--r-sm);
    overflow: hidden;
    height: 26px;
    min-width: 0;
    flex-shrink: 1;
    box-shadow: 0 1px 2px rgba(20,30,50,.06);
}
.node-chip-icon {
    width: 26px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}
.node-chip-id {
    padding: 0 7px;
    font-size: 12px;
    font-family: var(--mono, monospace);
    color: var(--ink-2);
    background: var(--surface);
    display: flex;
    align-items: center;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100px;
}
.edge-arrow {
    flex-shrink: 0;
    color: var(--ink-4);
}
.divider {
    height: 1px;
    background: var(--bdr);
    margin: 10px 0;
}
</style>
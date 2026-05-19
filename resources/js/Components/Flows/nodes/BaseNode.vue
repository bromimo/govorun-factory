<script setup>
import { computed } from 'vue';
import { Handle, Position } from '@vue-flow/core';
import { NODE_TYPE_COLORS } from '@/Components/Blocks/blockTypes.js';

defineOptions({ inheritAttrs: false });

const props = defineProps({
    id: String,
    label: String,
    type: String,
    hasInput: { type: Boolean, default: true },
    hasOutput: { type: Boolean, default: true },
    hasValidation: { type: Boolean, default: false },
    selected: Boolean,
});

const stripeColor = computed(() => NODE_TYPE_COLORS[props.type]?.stripe ?? '#9aa5b2');
</script>

<template>
    <div class="base-node" :class="{ 'base-node--selected': selected }">
        <Handle v-if="hasInput" type="target" :position="Position.Top" :connectable-start="false" />

        <div class="node-header">
            <span>{{ label }}</span>
            <slot name="header-right" />
        </div>

        <div class="node-body">
            <slot />
        </div>

        <div v-if="hasValidation" class="node-validation-badge" title="Валидация настроена">
            <svg class="h-3 w-3 text-blue-500" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M9.661 2.237a.531.531 0 01.678 0 11.947 11.947 0 007.078 2.749.5.5 0 01.479.425c.069.52.104 1.05.104 1.59 0 5.162-3.26 9.563-7.834 11.256a.48.48 0 01-.332 0C5.26 16.564 2 12.163 2 7c0-.54.035-1.07.104-1.59a.5.5 0 01.48-.425 11.947 11.947 0 007.077-2.75zm4.196 5.954a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
            </svg>
        </div>

        <Handle v-if="hasOutput" type="source" :position="Position.Bottom" />
        <slot v-else name="output-handles" />
    </div>
</template>

<style scoped>
.base-node {
    background: var(--surface);
    border: 1px solid var(--bdr);
    border-radius: var(--r-md);
    border-left: 10px solid v-bind(stripeColor);
    font-family: var(--font);
    font-size: 12px;
    min-width: 158px;
    box-shadow: var(--sh);
}
.base-node--selected {
    outline: 2px solid v-bind(stripeColor);
    outline-offset: 1px;
}
.node-header {
    padding: 5px 10px;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .04em;
    color: v-bind(stripeColor);
    border-bottom: 1px solid var(--bdr-l);
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.node-body {
    padding: 6px 10px;
    font-size: 12px;
    color: var(--ink-2);
}
.node-validation-badge {
    position: absolute;
    top: -6px;
    right: -6px;
    border-radius: 9999px;
    background: white;
    padding: 2px;
    box-shadow: 0 1px 2px rgba(0,0,0,.15);
    border: 1px solid var(--bdr, #e2e8f0);
}
</style>
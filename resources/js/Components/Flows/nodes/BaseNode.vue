<script setup>
import { Handle, Position } from '@vue-flow/core';
import { colorClasses } from '../../Blocks/blockTypes.js';

const props = defineProps({
    id: String,
    label: String,
    color: { type: String, default: 'gray' },
    hasInput: { type: Boolean, default: true },
    hasOutput: { type: Boolean, default: true },
    selected: Boolean,
});

const colors = colorClasses[props.color] ?? colorClasses.gray;
</script>

<template>
    <div class="rounded-lg border-2 shadow-sm min-w-[160px] max-w-[220px]"
        :class="[colors.border, colors.bg, selected ? 'ring-2 ring-indigo-400' : '']">
        <Handle v-if="hasInput" type="target" :position="Position.Top" :connectable-start="false" />

        <div class="px-3 py-1.5 text-xs font-bold border-b" :class="[colors.text, colors.border]">
            {{ label }}
        </div>

        <div class="px-3 py-2 text-xs text-gray-700">
            <slot />
        </div>

        <Handle v-if="hasOutput" type="source" :position="Position.Bottom" />
    </div>
</template>

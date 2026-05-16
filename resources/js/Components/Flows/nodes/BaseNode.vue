<script setup>
import { Handle, Position } from '@vue-flow/core';
import { colorClasses } from '../../Blocks/blockTypes.js';

const props = defineProps({
    id: String,
    label: String,
    color: { type: String, default: 'gray' },
    hasInput: { type: Boolean, default: true },
    hasOutput: { type: Boolean, default: true },
    hasValidation: { type: Boolean, default: false },
    selected: Boolean,
});

const colors = colorClasses[props.color] ?? colorClasses.gray;
</script>

<template>
    <div class="relative rounded-lg border-2 shadow-sm min-w-[160px] max-w-[220px]"
        :class="[colors.border, colors.bg, selected ? 'ring-2 ring-indigo-400' : '']">
        <Handle v-if="hasInput" type="target" :position="Position.Top" :connectable-start="false" />

        <div class="flex items-center justify-between px-3 py-1.5 text-xs font-bold border-b" :class="[colors.text, colors.border]">
            <span>{{ label }}</span>
            <slot name="header-right" />
        </div>

        <div class="px-3 py-2 text-xs text-gray-700">
            <slot />
        </div>

        <div v-if="hasValidation" class="absolute -top-1.5 -right-1.5 rounded-full bg-white p-0.5 shadow-sm border border-gray-200" title="Валидация настроена">
            <svg class="h-3 w-3 text-blue-500" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M9.661 2.237a.531.531 0 01.678 0 11.947 11.947 0 007.078 2.749.5.5 0 01.479.425c.069.52.104 1.05.104 1.59 0 5.162-3.26 9.563-7.834 11.256a.48.48 0 01-.332 0C5.26 16.564 2 12.163 2 7c0-.54.035-1.07.104-1.59a.5.5 0 01.48-.425 11.947 11.947 0 007.077-2.75zm4.196 5.954a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
            </svg>
        </div>

        <Handle v-if="hasOutput" type="source" :position="Position.Bottom" />
        <slot v-else name="output-handles" />
    </div>
</template>

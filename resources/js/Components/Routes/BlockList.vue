<script setup>
import { controllerBlockTypes, defaultBlockParams, colorClasses } from '../Blocks/blockTypes.js';
import BlockFormResolver from '../Blocks/BlockFormResolver.vue';

const model = defineModel({ type: Array, default: () => [] });

function addBlock(type) {
    model.value = [...model.value, { type, params: { ...defaultBlockParams[type] } }];
}

function removeBlock(index) {
    model.value = model.value.filter((_, i) => i !== index);
}

function moveBlock(index, direction) {
    const arr = [...model.value];
    const target = index + direction;
    if (target < 0 || target >= arr.length) return;
    [arr[index], arr[target]] = [arr[target], arr[index]];
    model.value = arr;
}
</script>

<template>
    <div class="space-y-3">
        <div v-for="(block, i) in model" :key="i"
            class="rounded-lg border p-3" :class="colorClasses[controllerBlockTypes.find(b => b.type === block.type)?.color ?? 'gray']?.border">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold"
                    :class="colorClasses[controllerBlockTypes.find(b => b.type === block.type)?.color ?? 'gray']?.text">
                    {{ controllerBlockTypes.find(b => b.type === block.type)?.label ?? block.type }}
                </span>
                <div class="flex items-center gap-1">
                    <button type="button" @click="moveBlock(i, -1)" :disabled="i === 0" class="text-gray-400 hover:text-gray-600 text-xs disabled:opacity-30">^</button>
                    <button type="button" @click="moveBlock(i, 1)" :disabled="i === model.length - 1" class="text-gray-400 hover:text-gray-600 text-xs disabled:opacity-30">v</button>
                    <button type="button" @click="removeBlock(i)" class="text-red-400 hover:text-red-600 text-xs ml-2">x</button>
                </div>
            </div>
            <BlockFormResolver :type="block.type" v-model="block.params" />
        </div>

        <div class="flex flex-wrap gap-2 pt-2">
            <button v-for="bt in controllerBlockTypes" :key="bt.type" type="button" @click="addBlock(bt.type)"
                class="rounded px-2 py-1 text-xs font-medium" :class="[colorClasses[bt.color]?.badge, colorClasses[bt.color]?.text]">
                + {{ bt.label }}
            </button>
        </div>
    </div>
</template>

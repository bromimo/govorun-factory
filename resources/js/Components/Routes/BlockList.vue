<script setup>
import { computed } from 'vue';
import { controllerBlockTypes, defaultBlockParams, colorClasses } from '../Blocks/blockTypes.js';
import BlockFormResolver from '../Blocks/BlockFormResolver.vue';

const model = defineModel({ type: Array, default: () => [] });
const props = defineProps({
    botId: { type: [Number, String], default: null },
});

function blockStateKeys(block) {
    if (block.type === 'save_state') {
        return (block.params?.variables ?? []).map(v => v.key).filter(Boolean);
    }
    if (block.type === 'api_call') {
        return (block.params?.response_mapping ?? []).map(m => m.state_key).filter(Boolean);
    }
    return [];
}

const allStateKeys = computed(() => [...new Set(model.value.flatMap(blockStateKeys))]);

function declaredBefore(index) {
    return [...new Set(model.value.slice(0, index).flatMap(blockStateKeys))];
}

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
            <BlockFormResolver :type="block.type" v-model="block.params" :bot-id="props.botId"
                :all-state-keys="allStateKeys"
                :declared-state-keys="declaredBefore(i)"
                :possibly-declared-state-keys="declaredBefore(i)" />
        </div>

        <div class="flex flex-wrap gap-2 pt-2">
            <button v-for="bt in controllerBlockTypes" :key="bt.type" type="button" @click="addBlock(bt.type)"
                class="rounded px-2 py-1 text-xs font-medium" :class="[colorClasses[bt.color]?.badge, colorClasses[bt.color]?.text]">
                + {{ bt.label }}
            </button>
        </div>
    </div>
</template>

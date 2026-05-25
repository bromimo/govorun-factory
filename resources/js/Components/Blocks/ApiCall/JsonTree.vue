<script setup>
import { computed } from 'vue';

const props = defineProps({
    value: {},
    path: { type: String, default: '' },
    mapping: { type: Array, default: () => [] },
});
const emit = defineEmits(['pick']);

const kind = computed(() => {
    if (props.value === null) return 'null';
    if (Array.isArray(props.value)) return 'array';
    if (typeof props.value === 'object') return 'object';
    return 'leaf';
});

const isLeaf = computed(() => kind.value === 'leaf' || kind.value === 'null');

const existingMapping = computed(() =>
    props.mapping.find(m => m.json_path === props.path) ?? null,
);

function pick() {
    emit('pick', props.path, props.value);
}
</script>

<template>
    <div class="font-mono text-xs">
        <template v-if="kind === 'leaf' || kind === 'null'">
            <span class="text-gray-600">{{ typeof value === 'string' ? `"${value}"` : String(value) }}</span>
            <button v-if="path && ! existingMapping" @click="pick"
                class="ml-2 text-indigo-600 hover:underline">[сохранить]</button>
            <span v-if="existingMapping" class="ml-2 text-green-700">← state.{{ existingMapping.state_key }} ✓</span>
        </template>

        <template v-else-if="kind === 'array'">
            <details open>
                <summary class="cursor-pointer text-gray-400">[ {{ value.length }} элементов ]</summary>
                <div class="pl-4">
                    <div v-for="(v, i) in value" :key="i">
                        <span class="text-purple-600">{{ i }}:</span>
                        <JsonTree :value="v" :path="path ? `${path}.${i}` : String(i)" :mapping="mapping" @pick="(p, v) => emit('pick', p, v)" />
                    </div>
                </div>
            </details>
        </template>

        <template v-else>
            <details open>
                <summary class="cursor-pointer text-gray-400">{ ... }</summary>
                <div class="pl-4">
                    <div v-for="(v, k) in value" :key="k">
                        <span class="text-blue-700">{{ k }}:</span>
                        <JsonTree :value="v" :path="path ? `${path}.${k}` : k" :mapping="mapping" @pick="(p, v) => emit('pick', p, v)" />
                    </div>
                </div>
            </details>
        </template>
    </div>
</template>

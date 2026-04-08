<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    edge: Object,
    canUpdate: Boolean,
    siblingLabels: { type: Array, default: () => [] },
});

const emit = defineEmits(['update', 'close', 'clear-waypoints']);

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
    <div class="border-l border-gray-200 bg-white p-4 overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-bold text-gray-700">Связь</h3>
            <button @click="emit('close')" class="text-gray-400 hover:text-gray-600 text-sm">x</button>
        </div>

        <div class="mb-3 space-y-2">
            <div>
                <label class="block text-xs font-medium text-gray-500">Источник</label>
                <p class="mt-0.5 text-xs text-gray-600 font-mono">{{ edge.source }}</p>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500">Цель</label>
                <p class="mt-0.5 text-xs text-gray-600 font-mono">{{ edge.target }}</p>
            </div>
        </div>

        <div>
            <label class="block text-xs font-medium text-gray-500">Метка</label>
            <div v-if="canUpdate" class="mt-0.5">
                <input v-model="localLabel" @blur="validateAndUpdateLabel" @keydown.enter="validateAndUpdateLabel"
                    class="w-full rounded border-gray-300 text-sm placeholder-gray-400"
                    :class="labelError ? 'border-red-400' : ''"
                    placeholder="yes, no, default..." />
                <p v-if="labelError" class="mt-0.5 text-xs text-red-500">{{ labelError }}</p>
            </div>
            <p v-else class="mt-0.5 text-xs text-gray-600">{{ edge.label || '—' }}</p>
        </div>

        <div v-if="canUpdate" class="mt-4">
            <button
                @click="emit('clear-waypoints', edge.id)"
                class="w-full rounded-md border border-gray-300 px-3 py-1.5 text-xs text-gray-600 hover:bg-gray-50"
            >
                Сбросить маршрут
            </button>
        </div>
    </div>
</template>

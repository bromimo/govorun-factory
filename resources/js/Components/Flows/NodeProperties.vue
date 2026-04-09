<script setup>
import { ref, computed, watch } from 'vue';
import { flowNodeTypes, colorClasses } from '../Blocks/blockTypes.js';
import BlockFormResolver from '../Blocks/BlockFormResolver.vue';

const props = defineProps({
    node: Object,
    canUpdate: Boolean,
    allNodeIds: { type: Array, default: () => [] },
    allStateKeys: { type: Array, default: () => [] },
    declaredStateKeys: { type: Array, default: () => [] },
    botValidationMessages: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['update', 'rename', 'close']);

const nodeType = computed(() => flowNodeTypes.find(t => t.type === props.node?.type));

const localData = ref({ ...props.node?.data });
const localId = ref(props.node?.id ?? '');
const idError = ref('');

watch(() => props.node?.id, (newId) => {
    localData.value = { ...props.node?.data };
    localId.value = newId ?? '';
    idError.value = '';
});

watch(localData, (val) => {
    if (props.node) {
        emit('update', props.node.id, { ...val });
    }
}, { deep: true });

function validateAndRenameId() {
    const newId = localId.value.trim();
    if (!newId) {
        idError.value = 'ID не может быть пустым';
        return;
    }
    if (newId === props.node.id) {
        idError.value = '';
        return;
    }
    if (!/^[a-zA-Z_][a-zA-Z0-9_]*$/.test(newId)) {
        idError.value = 'Только латиница, цифры и _';
        return;
    }
    if (props.allNodeIds.includes(newId)) {
        idError.value = 'Такой ID уже существует';
        return;
    }
    idError.value = '';
    emit('rename', props.node.id, newId);
}
</script>

<template>
    <div class="border-l border-gray-200 bg-white p-4 overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-bold" :class="colorClasses[nodeType?.color ?? 'gray']?.text">
                {{ nodeType?.label ?? node.type }}
            </h3>
            <button @click="emit('close')" class="text-gray-400 hover:text-gray-600 text-sm">x</button>
        </div>

        <div class="mb-3">
            <label class="block text-xs font-medium text-gray-500">ID узла</label>
            <div v-if="canUpdate" class="mt-0.5">
                <input v-model="localId" @blur="validateAndRenameId" @keydown.enter="validateAndRenameId"
                    class="w-full rounded border-gray-300 text-xs font-mono placeholder-gray-400"
                    :class="idError ? 'border-red-400' : ''" />
                <p v-if="idError" class="mt-0.5 text-xs text-red-500">{{ idError }}</p>
            </div>
            <p v-else class="mt-0.5 text-xs text-gray-600 font-mono">{{ node.id }}</p>
        </div>

        <div v-if="canUpdate">
            <BlockFormResolver :type="node.type" v-model="localData" :all-state-keys="allStateKeys" :declared-state-keys="declaredStateKeys" :bot-validation-messages="botValidationMessages" />
        </div>
        <div v-else class="text-xs text-gray-500">
            <pre class="whitespace-pre-wrap">{{ JSON.stringify(node.data, null, 2) }}</pre>
        </div>
    </div>
</template>

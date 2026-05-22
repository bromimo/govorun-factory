<script setup>
import { ref, computed } from 'vue';
import KeyboardQuickEdit from './KeyboardQuickEdit.vue';

const props = defineProps({
    modelValue: { type: Array, default: () => [] },
    keyboardType: { type: String, default: 'inline' },
});

const emit = defineEmits(['update:modelValue', 'open-full']);

const hasButtons = computed(() => {
    if (!Array.isArray(props.modelValue)) return false;
    return props.modelValue.some(row => Array.isArray(row) && row.length > 0);
});

function icon(btn) {
    if (!btn || typeof btn !== 'object') return '';
    if (btn.type === 'url') return '🔗';
    if (btn.type === 'contact') return '👤';
    if (btn.type === 'location') return '📍';
    return '';
}

const editing = ref(null);

function startEdit(rowIndex, btnIndex, event) {
    const target = event.currentTarget;
    const rect = target.getBoundingClientRect();
    editing.value = {
        rowIndex,
        btnIndex,
        anchor: {
            x: rect.left + rect.width / 2,
            y: rect.bottom,
        },
    };
}

function saveEdit(updatedBtn) {
    const { rowIndex, btnIndex } = editing.value;
    const next = props.modelValue.map((row, ri) =>
        ri === rowIndex
            ? row.map((b, bi) => (bi === btnIndex ? updatedBtn : b))
            : row,
    );
    emit('update:modelValue', next);
    editing.value = null;
}

function cancelEdit() {
    editing.value = null;
}

function openFull() {
    editing.value = null;
    emit('open-full');
}

const editingButton = computed(() =>
    editing.value
        ? props.modelValue[editing.value.rowIndex]?.[editing.value.btnIndex]
        : null,
);
</script>

<template>
    <div v-if="hasButtons" class="space-y-1 rounded border border-gray-200 bg-gray-50 p-2">
        <div v-if="keyboardType === 'reply'" class="text-xs font-medium text-gray-400">Reply</div>
        <div v-for="(row, ri) in modelValue" :key="ri" class="flex gap-1">
            <div
                v-for="(btn, bi) in row"
                :key="bi"
                class="flex-1 rounded bg-white border border-gray-300 px-2 py-1 text-xs text-gray-700 truncate text-center cursor-pointer select-none hover:border-blue-400 hover:bg-blue-50 transition-colors"
                title="Двойной клик — быстрая правка"
                @dblclick="startEdit(ri, bi, $event)"
            >
                {{ btn.label || '—' }}
                <span v-if="icon(btn)" class="ml-1">{{ icon(btn) }}</span>
            </div>
        </div>

        <KeyboardQuickEdit
            v-if="editing && editingButton"
            :button="editingButton"
            :keyboard-type="keyboardType"
            :anchor="editing.anchor"
            @save="saveEdit"
            @cancel="cancelEdit"
            @open-full="openFull"
        />
    </div>
</template>
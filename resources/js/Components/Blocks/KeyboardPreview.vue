<script setup>
import { computed } from 'vue';

const props = defineProps({
    modelValue: { type: Array, default: () => [] },
});

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
</script>

<template>
    <div v-if="hasButtons" class="space-y-1 rounded border border-gray-200 bg-gray-50 p-2">
        <div v-for="(row, ri) in modelValue" :key="ri" class="flex gap-1">
            <div v-for="(btn, bi) in row" :key="bi"
                class="flex-1 rounded bg-white border border-gray-300 px-2 py-1 text-xs text-gray-700 truncate text-center">
                {{ btn.label || '—' }}
                <span v-if="icon(btn)" class="ml-1">{{ icon(btn) }}</span>
            </div>
        </div>
    </div>
</template>

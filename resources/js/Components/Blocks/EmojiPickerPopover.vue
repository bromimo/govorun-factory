<script setup>
import 'emoji-picker-element';
import { ref, onMounted } from 'vue';
import { useClickOutside } from '@/utils/useClickOutside';

const emit = defineEmits(['select', 'close']);
const rootRef = ref(null);
const pickerRef = ref(null);

useClickOutside(rootRef, () => emit('close'));

onMounted(() => {
    pickerRef.value.addEventListener('emoji-click', (e) => {
        emit('select', e.detail.unicode);
    });
});
</script>

<template>
    <div ref="rootRef" class="absolute right-0 top-full z-50 mt-1 rounded border border-gray-200 bg-white shadow-lg">
        <emoji-picker ref="pickerRef"></emoji-picker>
    </div>
</template>

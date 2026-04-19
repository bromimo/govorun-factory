<script setup>
import 'emoji-picker-element';
import { ref } from 'vue';
import { useClickOutside } from '@/utils/useClickOutside';

const emit = defineEmits(['select', 'close']);
const rootRef = ref(null);

useClickOutside(rootRef, () => emit('close'));

function onPick(e) {
    emit('select', e.detail.unicode);
}
</script>

<template>
    <div ref="rootRef" class="absolute right-0 top-full z-50 mt-1 rounded border border-gray-200 bg-white shadow-lg">
        <emoji-picker @emoji-click="onPick"></emoji-picker>
    </div>
</template>

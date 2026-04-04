<script setup>
import { ref, watch, onBeforeUnmount } from 'vue';

const open = ref(false);
const panelRef = ref(null);

function handleClick(e) {
    if (panelRef.value && !panelRef.value.contains(e.target)) {
        open.value = false;
    }
}

watch(open, (val) => {
    if (val) {
        setTimeout(() => document.addEventListener('click', handleClick), 0);
    } else {
        document.removeEventListener('click', handleClick);
    }
});

onBeforeUnmount(() => document.removeEventListener('click', handleClick));
</script>

<template>
    <div ref="panelRef" class="absolute bottom-3 left-52 z-10">
        <button v-if="!open" @click="open = true"
            class="rounded bg-white/90 px-2 py-1 text-xs text-gray-500 shadow backdrop-blur hover:text-gray-700">
            ? Горячие клавиши
        </button>
        <div v-else class="rounded-lg bg-white/95 p-3 shadow-lg backdrop-blur text-xs text-gray-600 space-y-1.5 min-w-[220px]">
            <div class="flex items-center justify-between mb-2">
                <span class="font-medium text-gray-700">Горячие клавиши</span>
                <button @click="open = false" class="text-gray-400 hover:text-gray-600">x</button>
            </div>
            <div class="flex justify-between"><span>Выделить рамкой</span><kbd class="ml-3 rounded bg-gray-100 px-1.5 py-0.5 font-mono">Shift + drag</kbd></div>
            <div class="flex justify-between"><span>Добавить к выделению</span><kbd class="ml-3 rounded bg-gray-100 px-1.5 py-0.5 font-mono">Shift + click</kbd></div>
            <div class="flex justify-between"><span>Копировать</span><kbd class="ml-3 rounded bg-gray-100 px-1.5 py-0.5 font-mono">Ctrl + C</kbd></div>
            <div class="flex justify-between"><span>Вставить</span><kbd class="ml-3 rounded bg-gray-100 px-1.5 py-0.5 font-mono">Ctrl + V</kbd></div>
            <div class="flex justify-between"><span>Удалить выделенное</span><kbd class="ml-3 rounded bg-gray-100 px-1.5 py-0.5 font-mono">Delete / Backspace</kbd></div>
            <div class="flex justify-between"><span>Масштаб</span><kbd class="ml-3 rounded bg-gray-100 px-1.5 py-0.5 font-mono">Ctrl + scroll</kbd></div>
            <div class="flex justify-between"><span>Перемещение холста</span><kbd class="ml-3 rounded bg-gray-100 px-1.5 py-0.5 font-mono">drag по фону</kbd></div>
        </div>
    </div>
</template>

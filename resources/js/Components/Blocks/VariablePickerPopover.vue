<script setup>
import { ref, computed } from 'vue';
import { SYSTEM_VARS } from '@/utils/systemVars';
import { useClickOutside } from '@/utils/useClickOutside';

const props = defineProps({
    declaredKeys: { type: Array, default: () => [] },
});
const emit = defineEmits(['select', 'close']);

const rootRef = ref(null);
useClickOutside(rootRef, () => emit('close'));

const flowVars = computed(() =>
    [...props.declaredKeys].sort().map(key => ({ key })),
);
</script>

<template>
    <div ref="rootRef" class="absolute right-0 top-full z-50 mt-1 w-56 max-h-64 overflow-y-auto rounded border border-gray-200 bg-white shadow-lg">
        <section v-if="flowVars.length">
            <h4 class="bg-gray-50 px-3 py-1.5 text-xs font-semibold uppercase tracking-wide text-gray-500">Переменные потока</h4>
            <button v-for="v in flowVars" :key="v.key" type="button"
                @click="emit('select', v.key)"
                class="block w-full truncate px-3 py-1.5 text-left font-mono text-sm text-gray-700 hover:bg-indigo-50">
                {{ v.key }}
            </button>
        </section>
        <section>
            <h4 class="bg-gray-50 px-3 py-1.5 text-xs font-semibold uppercase tracking-wide text-gray-500">Данные пользователя</h4>
            <button v-for="v in SYSTEM_VARS" :key="v.key" type="button"
                @click="emit('select', v.key)"
                class="flex w-full items-center justify-between gap-2 px-3 py-1.5 text-left hover:bg-indigo-50">
                <span class="truncate font-mono text-sm text-gray-700">{{ v.key }}</span>
                <span class="shrink-0 text-xs text-gray-400">{{ v.label }}</span>
            </button>
        </section>
    </div>
</template>

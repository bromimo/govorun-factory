<script setup>
import { router } from '@inertiajs/vue3';

const props = defineProps({ plugin: Object });

function toggleActive() {
    router.put(route('plugins.update', props.plugin.id), {
        active: !props.plugin.active,
    });
}

function deletePlugin() {
    if (confirm(`Удалить плагин ${props.plugin.name}?`)) {
        router.delete(route('plugins.destroy', props.plugin.id));
    }
}
</script>

<template>
    <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-900/5">
        <div class="flex items-start justify-between">
            <div>
                <h3 class="font-semibold text-gray-900">{{ plugin.name }}</h3>
                <p v-if="plugin.description" class="mt-1 text-sm text-gray-500">{{ plugin.description }}</p>
            </div>
            <button @click="toggleActive"
                class="rounded-full px-2.5 py-0.5 text-xs font-medium"
                :class="plugin.active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-500'">
                {{ plugin.active ? 'Активен' : 'Отключён' }}
            </button>
        </div>

        <div class="mt-3 text-xs text-gray-400">
            <span v-if="plugin.vue_component">Vue: {{ plugin.vue_component }}</span>
        </div>

        <div class="mt-3 flex justify-end">
            <button @click="deletePlugin" class="text-xs text-red-600 hover:text-red-800">Удалить</button>
        </div>
    </div>
</template>

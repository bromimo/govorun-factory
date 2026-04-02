<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { colorClasses } from '../Blocks/blockTypes.js';
import RouteEditor from './RouteEditor.vue';

const props = defineProps({
    botId: Number,
    routes: Array,
    flows: Array,
    canUpdate: Boolean,
});

const showEditor = ref(false);
const editingRoute = ref(null);

function openCreate() {
    editingRoute.value = null;
    showEditor.value = true;
}

function openEdit(route) {
    editingRoute.value = route;
    showEditor.value = true;
}

function closeEditor() {
    showEditor.value = false;
    editingRoute.value = null;
}

function deleteRoute(route) {
    if (confirm('Удалить маршрут?')) {
        router.delete(window.route('bot-routes.destroy', [props.botId, route.id]));
    }
}

const typeColors = {
    command: 'blue', phrase: 'green', pattern: 'purple', action: 'orange',
    event: 'gray', media: 'pink', fallback: 'gray', location: 'green',
    contact: 'green', referral: 'orange',
};
</script>

<template>
    <div>
        <div v-if="routes.length" class="divide-y divide-gray-100">
            <div v-for="r in routes" :key="r.id" class="flex items-center gap-3 py-3">
                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
                    :class="[colorClasses[typeColors[r.type] ?? 'gray']?.badge, colorClasses[typeColors[r.type] ?? 'gray']?.text]">
                    {{ r.type }}
                </span>
                <span v-if="r.match" class="text-sm text-gray-700">{{ r.match }}</span>
                <span class="text-xs text-gray-400">{{ r.handler_type }}</span>
                <div v-if="canUpdate" class="ml-auto flex gap-2">
                    <button @click="openEdit(r)" class="text-xs text-indigo-600 hover:text-indigo-800">Изменить</button>
                    <button @click="deleteRoute(r)" class="text-xs text-red-600 hover:text-red-800">Удалить</button>
                </div>
            </div>
        </div>
        <p v-else class="text-sm text-gray-500">Нет маршрутов</p>

        <button v-if="canUpdate" @click="openCreate"
            class="mt-4 rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-500">
            Добавить маршрут
        </button>

        <RouteEditor v-if="showEditor" :bot-id="botId" :route="editingRoute" :flows="flows" @close="closeEditor" />
    </div>
</template>

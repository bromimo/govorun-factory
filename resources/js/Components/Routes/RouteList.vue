<script setup>
import { ref, watch } from 'vue';
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
const editorParentId = ref(null);
const localRoutes = ref(props.routes ? [...props.routes] : []);
const dragIndex = ref(null);
const overIndex = ref(null);

watch(() => props.routes, (val) => { localRoutes.value = [...val]; });

function openCreate(parentId = null) {
    editingRoute.value = null;
    editorParentId.value = parentId;
    showEditor.value = true;
}

function openEdit(route) {
    editingRoute.value = route;
    editorParentId.value = route.parent_id ?? null;
    showEditor.value = true;
}

function closeEditor() {
    showEditor.value = false;
    editingRoute.value = null;
    editorParentId.value = null;
}

function deleteRoute(route) {
    if (confirm('Удалить маршрут?')) {
        router.delete(window.route('bot-routes.destroy', [props.botId, route.id]));
    }
}

function onDragStart(e, index) {
    dragIndex.value = index;
    e.dataTransfer.effectAllowed = 'move';
}

function onDragOver(e, index) {
    e.preventDefault();
    overIndex.value = index;
}

function onDrop() {
    if (dragIndex.value === null || overIndex.value === null || dragIndex.value === overIndex.value) return;

    const list = [...localRoutes.value];
    const [moved] = list.splice(dragIndex.value, 1);
    list.splice(overIndex.value, 0, moved);
    localRoutes.value = list;

    const ids = list.map(r => r.id);
    window.axios.post(window.route('bot-routes.reorder', props.botId), { ids });
}

function onDragEnd() {
    dragIndex.value = null;
    overIndex.value = null;
}

const typeColors = {
    command: 'blue', phrase: 'green', pattern: 'purple', action: 'orange',
    event: 'gray', media: 'pink', fallback: 'gray', location: 'green',
    contact: 'green', referral: 'orange',
};
</script>

<template>
    <div>
        <div v-if="localRoutes.length" class="divide-y divide-gray-100">
            <template v-for="(r, index) in localRoutes" :key="r.id">
                <div class="flex items-center gap-3 py-3 transition-colors"
                    :class="{ 'border-t-2 border-indigo-400': overIndex === index && dragIndex !== index }"
                    :draggable="canUpdate"
                    @dragstart="onDragStart($event, index)"
                    @dragover="onDragOver($event, index)"
                    @drop="onDrop"
                    @dragend="onDragEnd">
                    <svg v-if="canUpdate" class="h-4 w-4 shrink-0 cursor-grab text-gray-300" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M7 2a2 2 0 10.001 4.001A2 2 0 007 2zm0 6a2 2 0 10.001 4.001A2 2 0 007 8zm0 6a2 2 0 10.001 4.001A2 2 0 007 14zm6-8a2 2 0 10-.001-4.001A2 2 0 0013 6zm0 2a2 2 0 10.001 4.001A2 2 0 0013 8zm0 6a2 2 0 10.001 4.001A2 2 0 0013 14z" />
                    </svg>
                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
                        :class="[colorClasses[typeColors[r.type] ?? 'gray']?.badge, colorClasses[typeColors[r.type] ?? 'gray']?.text]">
                        {{ r.type }}
                    </span>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span v-if="r.match" class="text-sm text-gray-700">{{ r.match }}</span>
                            <span class="text-xs text-gray-400">{{ r.handler_type }}</span>
                        </div>
                        <div v-if="r.aliases?.length" class="mt-1 flex flex-wrap gap-1">
                            <span v-for="alias in r.aliases" :key="alias"
                                class="inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">
                                {{ alias }}
                            </span>
                        </div>
                    </div>
                    <div v-if="canUpdate" class="ml-auto flex gap-2 shrink-0">
                        <button v-if="r.type === 'phrase' && !r.parent_id" @click="openCreate(r.id)"
                            class="text-xs text-green-600 hover:text-green-800">+ Вложенный</button>
                        <button @click="openEdit(r)" class="text-xs text-indigo-600 hover:text-indigo-800">Изменить</button>
                        <button @click="deleteRoute(r)" class="text-xs text-red-600 hover:text-red-800">Удалить</button>
                    </div>
                </div>
                <!-- Дочерние маршруты -->
                <div v-for="child in r.children ?? []" :key="child.id"
                    class="flex items-center gap-3 py-2 pl-10">
                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
                        :class="[colorClasses[typeColors[child.type] ?? 'gray']?.badge, colorClasses[typeColors[child.type] ?? 'gray']?.text]">
                        {{ child.type }}
                    </span>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span v-if="child.match" class="text-sm text-gray-700">{{ child.match }}</span>
                            <span class="text-xs text-gray-400">{{ child.handler_type }}</span>
                        </div>
                        <div v-if="child.aliases?.length" class="mt-1 flex flex-wrap gap-1">
                            <span v-for="alias in child.aliases" :key="alias"
                                class="inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">
                                {{ alias }}
                            </span>
                        </div>
                    </div>
                    <div v-if="canUpdate" class="ml-auto flex gap-2 shrink-0">
                        <button @click="openEdit(child)" class="text-xs text-indigo-600 hover:text-indigo-800">Изменить</button>
                        <button @click="deleteRoute(child)" class="text-xs text-red-600 hover:text-red-800">Удалить</button>
                    </div>
                </div>
            </template>
        </div>
        <p v-if="!localRoutes.length" class="text-sm text-gray-500">Нет маршрутов</p>

        <button v-if="canUpdate" @click="openCreate"
            class="mt-4 rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-500">
            Добавить маршрут
        </button>

        <RouteEditor v-if="showEditor" :bot-id="botId" :route="editingRoute" :parent-id="editorParentId" :flows="flows" @close="closeEditor" />
    </div>
</template>

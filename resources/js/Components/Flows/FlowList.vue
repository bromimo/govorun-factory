<script setup>
import { router, Link } from '@inertiajs/vue3';

const props = defineProps({
    botId: Number,
    flows: Array,
    canUpdate: Boolean,
});

function createFlow() {
    const name = prompt('Название диалога:');
    if (!name) return;
    const description = prompt('Описание (необязательно):') || '';
    router.post(route('bot-flows.store', props.botId), { name, description });
}

function deleteFlow(flow) {
    if (confirm(`Удалить диалог ${flow.name}?`)) {
        router.delete(route('bot-flows.destroy', [props.botId, flow.id]));
    }
}
</script>

<template>
    <div>
        <div v-if="flows.length" class="divide-y divide-gray-100">
            <div v-for="f in flows" :key="f.id" class="flex items-center justify-between py-3">
                <div>
                    <Link :href="route('bot-flows.show', [botId, f.id])"
                        class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                        {{ f.name }}
                    </Link>
                    <p v-if="f.description" class="text-xs text-gray-500">{{ f.description }}</p>
                </div>
                <button v-if="canUpdate" @click="deleteFlow(f)" class="text-xs text-red-600 hover:text-red-800">
                    Удалить
                </button>
            </div>
        </div>
        <p v-else class="text-sm text-gray-500">Нет диалогов</p>

        <button v-if="canUpdate" @click="createFlow"
            class="mt-4 rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-500">
            Добавить диалог
        </button>
    </div>
</template>

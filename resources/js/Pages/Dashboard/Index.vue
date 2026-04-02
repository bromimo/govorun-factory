<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import BotCard from '@/Components/Bots/BotCard.vue';

const props = defineProps({
    bots: Array,
    filters: Object,
    can: Object,
});

const search = ref(props.filters?.search ?? '');

function doSearch() {
    router.get('/', { search: search.value || undefined }, { preserveState: true });
}

function createBot() {
    const name = prompt('Название бота:');
    if (name) {
        router.post(route('bots.store'), { name });
    }
}
</script>

<template>
    <Head title="Боты" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Боты</h2>
                <button v-if="can.createBot" @click="createBot"
                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500">
                    Новый бот
                </button>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="mb-6">
                    <input v-model="search" @keyup.enter="doSearch" type="text"
                        placeholder="Поиск по названию..."
                        class="w-full max-w-sm rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                </div>

                <div v-if="bots.length" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <BotCard v-for="bot in bots" :key="bot.id" :bot="bot" />
                </div>
                <div v-else class="rounded-lg border-2 border-dashed border-gray-300 p-12 text-center">
                    <p class="text-sm text-gray-500">
                        Нет ботов{{ filters?.search ? ' по запросу' : '' }}
                    </p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

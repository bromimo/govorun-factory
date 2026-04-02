<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    bot: Object,
    can: Object,
});
</script>

<template>
    <Head :title="bot.name" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ bot.name }}</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <p class="text-gray-500">Редактор бота</p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

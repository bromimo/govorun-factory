<script setup>
import { Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import MediaLibrary from '@/Components/Bots/MediaLibrary.vue';

const props = defineProps({
    bot: Object,
    media: Array,
});
</script>

<template>
    <AuthenticatedLayout :title="`Медиатека — ${bot.name}`">
        <template #breadcrumbs>
            <Link :href="route('dashboard')">Главная</Link>
            <span class="sep">›</span>
            <Link :href="route('bots.edit', bot.id)">{{ bot.name }}</Link>
            <span class="sep">›</span>
            <span>Медиа</span>
        </template>
        <div class="mx-auto max-w-4xl px-4 py-6">
            <div class="mb-4 flex items-center gap-3">
                <Link :href="route('bots.edit', bot.id)" class="text-sm text-gray-500 hover:text-gray-700">
                    ← Назад к боту
                </Link>
                <h1 class="text-xl font-semibold">Медиатека</h1>
            </div>
            <MediaLibrary :bot="bot" :initial-items="media" />
        </div>
    </AuthenticatedLayout>
</template>

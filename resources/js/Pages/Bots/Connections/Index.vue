<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineProps({
    bot: Object,
    connections: Array,
});
</script>

<template>
    <AuthenticatedLayout :title="`Подключения — ${bot.name}`">
        <div class="mx-auto max-w-4xl px-4 py-6">
            <h1 class="text-xl font-semibold">Подключения к внешним API</h1>
        </div>
    </AuthenticatedLayout>
</template>

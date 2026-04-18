<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    bot: Object,
});
</script>

<template>
    <Link :href="route('bots.edit', bot.id)"
        class="block rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-900/5 transition hover:shadow-md">
        <h3 class="text-lg font-semibold text-gray-900">{{ bot.name }}</h3>
        <p v-if="bot.description" class="mt-1 text-sm text-gray-500 line-clamp-2">{{ bot.description }}</p>

        <div class="mt-4 flex items-center gap-4 text-xs text-gray-400">
            <span>{{ bot.routes_count ?? 0 }} маршрутов</span>
            <span>{{ bot.flows_count ?? 0 }} диалогов</span>
        </div>

        <div class="mt-2 flex items-center justify-between text-xs text-gray-400">
            <span>{{ bot.updater?.name ?? '—' }}</span>
            <span>{{ new Date(bot.updated_at).toLocaleDateString('ru') }}</span>
        </div>
    </Link>
</template>

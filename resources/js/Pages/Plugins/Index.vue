<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import PluginCard from '@/Components/Plugins/PluginCard.vue';
import PluginUpload from '@/Components/Plugins/PluginUpload.vue';

defineProps({ plugins: Array });

const showUpload = ref(false);
</script>

<template>
    <Head title="Плагины" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Плагины</h2>
                <button @click="showUpload = true"
                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500">
                    Добавить плагин
                </button>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div v-if="plugins.length" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <PluginCard v-for="plugin in plugins" :key="plugin.id" :plugin="plugin" />
                </div>
                <p v-else class="text-sm text-gray-500">Нет установленных плагинов</p>
            </div>
        </div>

        <PluginUpload v-if="showUpload" @close="showUpload = false" />
    </AuthenticatedLayout>
</template>

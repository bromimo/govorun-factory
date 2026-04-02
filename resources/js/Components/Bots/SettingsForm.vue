<script setup>
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    bot: Object,
    can: Object,
});

const form = useForm({
    name: props.bot.name,
    description: props.bot.description ?? '',
    config: {
        environment: props.bot.config?.environment ?? 'development',
        debug: props.bot.config?.debug ?? true,
        state_storage: props.bot.config?.state_storage ?? 'file',
    },
});

function save() {
    form.put(route('bots.update', props.bot.id));
}
</script>

<template>
    <form @submit.prevent="save" class="space-y-6">
        <div>
            <label class="block text-sm font-medium text-gray-700">Название</label>
            <input v-model="form.name" type="text"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
            <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Описание</label>
            <textarea v-model="form.description" rows="3"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
            <p v-if="form.errors.description" class="mt-1 text-sm text-red-600">{{ form.errors.description }}</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Окружение</label>
            <select v-model="form.config.environment"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                <option value="development">Development</option>
                <option value="production">Production</option>
            </select>
        </div>

        <div class="flex items-center gap-2">
            <input v-model="form.config.debug" type="checkbox" id="debug"
                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
            <label for="debug" class="text-sm font-medium text-gray-700">Debug mode</label>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">State Storage</label>
            <select v-model="form.config.state_storage"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                <option value="file">File</option>
                <option value="database">Database</option>
                <option value="cache">Cache</option>
            </select>
        </div>

        <div v-if="can.update" class="pt-4">
            <button type="submit" :disabled="form.processing"
                class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500 disabled:opacity-50">
                Сохранить настройки
            </button>
            <span v-if="form.recentlySuccessful" class="ml-3 text-sm text-green-600">Сохранено</span>
        </div>
    </form>
</template>

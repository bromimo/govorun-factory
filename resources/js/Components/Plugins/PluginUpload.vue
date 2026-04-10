<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const emit = defineEmits(['close']);

const form = useForm({
    name: '',
    description: '',
    block_schema: {},
    vue_component: '',
    php_stub: '',
    active: true,
});

const schemaText = ref('{}');

watch(schemaText, (val) => {
    try { form.block_schema = JSON.parse(val); } catch {}
});

function submit() {
    form.post(route('plugins.store'), {
        onSuccess: () => emit('close'),
    });
}
</script>

<template>
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="emit('close')">
        <div class="w-full max-w-lg rounded-lg bg-white p-6 shadow-xl max-h-[90vh] overflow-y-auto">
            <h3 class="mb-4 text-lg font-medium">Добавить плагин</h3>

            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Имя (тип блока)</label>
                    <input v-model="form.name" type="text" class="mt-1 w-full rounded-md border-gray-300 text-sm" placeholder="send_email" />
                    <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Описание</label>
                    <input v-model="form.description" type="text" class="mt-1 w-full rounded-md border-gray-300 text-sm" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">JSON Schema (параметры блока)</label>
                    <textarea v-model="schemaText" rows="4" class="mt-1 w-full rounded-md border-gray-300 font-mono text-sm" placeholder='{"to": "string", "subject": "string"}' />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Vue-компонент</label>
                    <input v-model="form.vue_component" type="text" class="mt-1 w-full rounded-md border-gray-300 text-sm" placeholder="SendEmailForm" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">PHP Stub (Blade-шаблон)</label>
                    <textarea v-model="form.php_stub" rows="4" class="mt-1 w-full rounded-md border-gray-300 font-mono text-sm"
                        placeholder="        $this->sendEmail('{{ $params['to'] }}');" />
                </div>

                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" @click="emit('close')"
                        class="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                        Отмена
                    </button>
                    <button type="submit" :disabled="form.processing"
                        class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500 disabled:opacity-50">
                        Создать
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

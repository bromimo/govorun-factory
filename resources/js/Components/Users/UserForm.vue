<script setup>
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    user: { type: Object, default: null },
});

const emit = defineEmits(['close']);

const isEditing = computed(() => !!props.user);

const form = useForm({
    name: props.user?.name ?? '',
    email: props.user?.email ?? '',
    password: '',
    password_confirmation: '',
    role: props.user?.role ?? 'viewer',
});

function submit() {
    if (isEditing.value) {
        form.put(route('users.update', props.user.id), {
            onSuccess: () => emit('close'),
        });
    } else {
        form.post(route('users.store'), {
            onSuccess: () => emit('close'),
        });
    }
}
</script>

<template>
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="emit('close')">
        <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
            <h3 class="mb-4 text-lg font-medium">
                {{ isEditing ? 'Редактировать' : 'Новый' }} пользователь
            </h3>

            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Имя</label>
                    <input v-model="form.name" type="text"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                    <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Email</label>
                    <input v-model="form.email" type="email"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                    <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        Пароль{{ isEditing ? ' (оставьте пустым, чтобы не менять)' : '' }}
                    </label>
                    <input v-model="form.password" type="password"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                    <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">{{ form.errors.password }}</p>
                </div>

                <div v-if="form.password">
                    <label class="block text-sm font-medium text-gray-700">Подтверждение пароля</label>
                    <input v-model="form.password_confirmation" type="password"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Роль</label>
                    <select v-model="form.role"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="admin">Admin</option>
                        <option value="editor">Editor</option>
                        <option value="viewer">Viewer</option>
                    </select>
                    <p v-if="form.errors.role" class="mt-1 text-sm text-red-600">{{ form.errors.role }}</p>
                </div>

                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" @click="emit('close')" class="er-btn">Отмена</button>
                    <button type="submit" :disabled="form.processing" class="er-btn pr">
                        {{ isEditing ? 'Сохранить' : 'Создать' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import UserForm from '@/Components/Users/UserForm.vue';

const props = defineProps({
    users: Array,
});

const showForm = ref(false);
const editingUser = ref(null);

function openCreate() {
    editingUser.value = null;
    showForm.value = true;
}

function openEdit(user) {
    editingUser.value = user;
    showForm.value = true;
}

function closeForm() {
    showForm.value = false;
    editingUser.value = null;
}

function deleteUser(user) {
    if (confirm(`Удалить пользователя ${user.name}?`)) {
        router.delete(route('users.destroy', user.id));
    }
}
</script>

<template>
    <Head title="Пользователи" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Пользователи</h2>
                <button @click="openCreate"
                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500">
                    Новый пользователь
                </button>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Имя</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Роль</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="user in users" :key="user.id">
                                <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">{{ user.name }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ user.email }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm">
                                    <span class="inline-flex rounded-full px-2 text-xs font-semibold leading-5"
                                        :class="{
                                            'bg-red-100 text-red-800': user.role === 'admin',
                                            'bg-blue-100 text-blue-800': user.role === 'editor',
                                            'bg-gray-100 text-gray-800': user.role === 'viewer',
                                        }">
                                        {{ user.role }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                    <button @click="openEdit(user)" class="mr-3 text-indigo-600 hover:text-indigo-900">Изменить</button>
                                    <button @click="deleteUser(user)" class="text-red-600 hover:text-red-900">Удалить</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <UserForm v-if="showForm" :user="editingUser" @close="closeForm" />
    </AuthenticatedLayout>
</template>

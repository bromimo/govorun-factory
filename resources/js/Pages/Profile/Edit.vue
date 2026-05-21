<script setup>
import { Head } from '@inertiajs/vue3';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';

defineProps({
    mustVerifyEmail: Boolean,
    status: String,
});
</script>

<template>
    <Head title="Профиль" />

    <AuthenticatedLayout title="Профиль">
        <template #breadcrumbs>
            <a :href="route('dashboard')">Главная</a>
            <span class="sep">›</span>
            <span>Профиль</span>
        </template>

        <div class="profile-wrap">
            <UpdateProfileInformationForm
                :must-verify-email="mustVerifyEmail"
                :status="status"
            />

            <UpdatePasswordForm />

            <DeleteUserForm />
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.profile-wrap {
    display: flex;
    flex-direction: column;
    gap: 16px;
    max-width: 640px;
}
</style>
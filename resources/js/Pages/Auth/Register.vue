<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import ErButton from '@/Components/Ui/ErButton.vue';
import ErInput from '@/Components/Ui/ErInput.vue';
import ErFormField from '@/Components/Ui/ErFormField.vue';
import ErFormSection from '@/Components/Ui/ErFormSection.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

function submit() {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
}
</script>

<template>
    <GuestLayout>
        <Head title="Регистрация" />

        <form @submit.prevent="submit" class="er-form-wrap">
            <ErFormSection title="Регистрация">
                <ErFormField label="Имя" required>
                    <ErInput id="name" v-model="form.name" autocomplete="name" autofocus required long />
                    <span v-if="form.errors.name" class="field-err">{{ form.errors.name }}</span>
                </ErFormField>

                <ErFormField label="Email" required>
                    <ErInput id="email" v-model="form.email" type="email" autocomplete="username" required long />
                    <span v-if="form.errors.email" class="field-err">{{ form.errors.email }}</span>
                </ErFormField>

                <ErFormField label="Пароль" required>
                    <ErInput id="password" v-model="form.password" type="password" autocomplete="new-password" required long />
                    <span v-if="form.errors.password" class="field-err">{{ form.errors.password }}</span>
                </ErFormField>

                <ErFormField label="Подтверждение пароля" required>
                    <ErInput
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        type="password"
                        autocomplete="new-password"
                        required
                        long
                    />
                    <span v-if="form.errors.password_confirmation" class="field-err">
                        {{ form.errors.password_confirmation }}
                    </span>
                </ErFormField>
            </ErFormSection>

            <div class="form-acts">
                <Link :href="route('login')" class="er-link">Уже зарегистрированы?</Link>
                <ErButton variant="primary" type="submit" :disabled="form.processing">
                    Зарегистрироваться
                </ErButton>
            </div>
        </form>
    </GuestLayout>
</template>

<style scoped>
.er-form-wrap { max-width: 420px; margin: 0 auto; }
.form-acts {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 12px;
}
.er-link {
    font-size: 12px;
    color: var(--blue);
    text-decoration: none;
}
.er-link:hover { text-decoration: underline; }
.field-err { display: block; font-size: 11px; color: var(--red); margin-top: 3px; }
</style>
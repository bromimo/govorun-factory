<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import ErButton from '@/Components/Ui/ErButton.vue';
import ErInput from '@/Components/Ui/ErInput.vue';
import ErFormField from '@/Components/Ui/ErFormField.vue';
import ErFormSection from '@/Components/Ui/ErFormSection.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({ password: '' });

function submit() {
    form.post(route('password.confirm'), {
        onFinish: () => form.reset(),
    });
}
</script>

<template>
    <GuestLayout>
        <Head title="Подтверждение пароля" />

        <form @submit.prevent="submit" class="er-form-wrap">
            <ErFormSection title="Подтверждение пароля">
                <p class="hint-text">
                    Это безопасная зона приложения. Подтвердите пароль, чтобы продолжить.
                </p>

                <ErFormField label="Пароль" required>
                    <ErInput
                        id="password"
                        v-model="form.password"
                        type="password"
                        autocomplete="current-password"
                        autofocus
                        required
                        long
                    />
                    <span v-if="form.errors.password" class="field-err">{{ form.errors.password }}</span>
                </ErFormField>
            </ErFormSection>

            <div class="form-acts">
                <ErButton variant="primary" type="submit" :disabled="form.processing">
                    Подтвердить
                </ErButton>
            </div>
        </form>
    </GuestLayout>
</template>

<style scoped>
.er-form-wrap { max-width: 420px; margin: 0 auto; }
.hint-text { font-size: 12px; color: var(--ink-3); margin: 0 0 12px; line-height: 1.4; }
.form-acts { display: flex; justify-content: flex-end; margin-top: 12px; }
.field-err { display: block; font-size: 11px; color: var(--red); margin-top: 3px; }
</style>

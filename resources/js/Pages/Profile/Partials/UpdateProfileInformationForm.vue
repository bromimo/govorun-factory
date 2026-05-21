<script setup>
import ErInput from '@/Components/Ui/ErInput.vue';
import ErButton from '@/Components/Ui/ErButton.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import ErFormField from '@/Components/Ui/ErFormField.vue';
import ErFormSection from '@/Components/Ui/ErFormSection.vue';

defineProps({
    mustVerifyEmail: Boolean,
    status: String,
});

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    email: user.email,
});
</script>

<template>
    <ErFormSection title="Личные данные">
        <form @submit.prevent="form.patch(route('profile.update'))">
            <ErFormField label="Имя" required>
                <ErInput id="name" v-model="form.name" autocomplete="name" required long />
                <span v-if="form.errors.name" class="field-err">{{ form.errors.name }}</span>
            </ErFormField>

            <ErFormField label="Email" required>
                <ErInput id="email" v-model="form.email" type="email" autocomplete="username" required long />
                <span v-if="form.errors.email" class="field-err">{{ form.errors.email }}</span>
            </ErFormField>

            <div v-if="mustVerifyEmail && user.email_verified_at === null" class="hint-text">
                Email не подтверждён.
                <Link
                    :href="route('verification.send')"
                    method="post"
                    as="button"
                    class="er-link"
                >
                    Отправить ссылку для подтверждения
                </Link>
                <span v-if="status === 'verification-link-sent'" class="success-inline">
                    Ссылка отправлена.
                </span>
            </div>

            <div class="form-acts">
                <ErButton variant="primary" type="submit" :disabled="form.processing">Сохранить</ErButton>
                <Transition enter-from-class="opacity-0" leave-to-class="opacity-0">
                    <span v-if="form.recentlySuccessful" class="saved-msg">Сохранено</span>
                </Transition>
            </div>
        </form>
    </ErFormSection>
</template>

<style scoped>
.hint-text { font-size: 11px; color: var(--ink-3); margin: 0 0 8px; }
.er-link {
    background: none; border: none; color: var(--blue);
    cursor: pointer; font-size: 11px; padding: 0; font-family: var(--font);
}
.er-link:hover { text-decoration: underline; }
.success-inline { color: var(--green); font-size: 11px; margin-left: 6px; }
.form-acts { display: flex; align-items: center; gap: 10px; margin-top: 8px; }
.saved-msg { font-size: 11px; color: var(--green); }
.field-err { display: block; font-size: 11px; color: var(--red); margin-top: 3px; }
</style>
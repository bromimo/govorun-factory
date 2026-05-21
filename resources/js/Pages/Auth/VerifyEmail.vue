<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import ErButton from '@/Components/Ui/ErButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({ status: { type: String } });

const form = useForm({});

const verificationLinkSent = computed(() => props.status === 'verification-link-sent');

function submit() {
    form.post(route('verification.send'));
}
</script>

<template>
    <GuestLayout>
        <Head title="Подтверждение email" />

        <div class="er-form-wrap">
            <h2 class="title">Подтверждение email</h2>

            <p class="hint-text">
                Спасибо за регистрацию! Прежде чем продолжить, подтвердите свой email,
                пройдя по ссылке, которую мы только что отправили. Если письмо не пришло,
                запросите новое.
            </p>

            <div v-if="verificationLinkSent" class="success-text">
                Новая ссылка для подтверждения отправлена на email, указанный при регистрации.
            </div>

            <form @submit.prevent="submit">
                <div class="form-acts">
                    <ErButton variant="primary" type="submit" :disabled="form.processing">
                        Отправить ссылку повторно
                    </ErButton>

                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="er-link"
                    >
                        Выйти
                    </Link>
                </div>
            </form>
        </div>
    </GuestLayout>
</template>

<style scoped>
.er-form-wrap { max-width: 480px; margin: 0 auto; }
.title { font-size: 14px; font-weight: 600; color: var(--ink); margin: 0 0 12px; }
.hint-text { font-size: 12px; color: var(--ink-2); line-height: 1.5; margin-bottom: 12px; }
.success-text {
    background: var(--green-soft);
    border: 1px solid var(--green);
    color: var(--ink);
    padding: 8px 12px;
    border-radius: var(--r-md);
    font-size: 12px;
    margin-bottom: 12px;
}
.form-acts {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
}
.er-link {
    background: none;
    border: none;
    color: var(--blue);
    cursor: pointer;
    font-size: 12px;
    font-family: var(--font);
    padding: 0;
}
.er-link:hover { text-decoration: underline; }
</style>

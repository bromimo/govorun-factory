<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import ErInput from '@/Components/Ui/ErInput.vue';
import ErButton from '@/Components/Ui/ErButton.vue';
import ErFormField from '@/Components/Ui/ErFormField.vue';
import ErFormSection from '@/Components/Ui/ErFormSection.vue';

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

function updatePassword() {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value?.focus();
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value?.focus();
            }
        },
    });
}
</script>

<template>
    <ErFormSection title="Смена пароля">
        <form @submit.prevent="updatePassword">
            <ErFormField label="Текущий пароль" required>
                <ErInput
                    ref="currentPasswordInput"
                    v-model="form.current_password"
                    type="password"
                    autocomplete="current-password"
                    required
                    long
                />
                <span v-if="form.errors.current_password" class="field-err">
                    {{ form.errors.current_password }}
                </span>
            </ErFormField>

            <ErFormField label="Новый пароль" required>
                <ErInput
                    ref="passwordInput"
                    v-model="form.password"
                    type="password"
                    autocomplete="new-password"
                    required
                    long
                />
                <span v-if="form.errors.password" class="field-err">{{ form.errors.password }}</span>
            </ErFormField>

            <ErFormField label="Подтверждение пароля" required>
                <ErInput
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
.form-acts { display: flex; align-items: center; gap: 10px; margin-top: 8px; }
.saved-msg { font-size: 11px; color: var(--green); }
.field-err { display: block; font-size: 11px; color: var(--red); margin-top: 3px; }
</style>
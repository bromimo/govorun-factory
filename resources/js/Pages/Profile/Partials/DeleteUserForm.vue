<script setup>
import { nextTick, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import ErInput from '@/Components/Ui/ErInput.vue';
import ErButton from '@/Components/Ui/ErButton.vue';
import ErFormField from '@/Components/Ui/ErFormField.vue';
import ErFormSection from '@/Components/Ui/ErFormSection.vue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({ password: '' });

function confirmUserDeletion() {
    confirmingUserDeletion.value = true;
    nextTick(() => passwordInput.value?.focus());
}

function deleteUser() {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value?.focus(),
        onFinish: () => form.reset(),
    });
}

function closeModal() {
    confirmingUserDeletion.value = false;
    form.reset();
}
</script>

<template>
    <ErFormSection title="Удалить аккаунт">
        <p class="hint-text">
            После удаления аккаунта все данные будут уничтожены. Перед удалением
            скачайте всё, что хотите сохранить.
        </p>

        <div class="form-acts">
            <ErButton variant="danger" @click="confirmUserDeletion">Удалить аккаунт</ErButton>
        </div>

        <Modal :show="confirmingUserDeletion" title="Удалить аккаунт?" @close="closeModal">
            <div class="modal-body">
                <p class="hint-text">
                    Это действие нельзя отменить. Введите пароль для подтверждения.
                </p>

                <ErFormField label="Пароль" required>
                    <ErInput
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        long
                        @keyup.enter="deleteUser"
                    />
                    <span v-if="form.errors.password" class="field-err">{{ form.errors.password }}</span>
                </ErFormField>
            </div>

            <div class="modal-foot">
                <ErButton @click="closeModal">Отмена</ErButton>
                <ErButton variant="danger" :disabled="form.processing" @click="deleteUser">
                    Удалить
                </ErButton>
            </div>
        </Modal>
    </ErFormSection>
</template>

<style scoped>
.hint-text { font-size: 12px; color: var(--ink-2); margin: 0 0 12px; line-height: 1.4; }
.form-acts { display: flex; }
.modal-body { padding: 12px 16px; }
.modal-foot {
    padding: 8px 12px;
    border-top: 1px solid var(--bdr);
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    background: var(--surface-2);
}
.field-err { display: block; font-size: 11px; color: var(--red); margin-top: 3px; }
</style>

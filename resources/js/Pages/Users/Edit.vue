<script setup>
import { ref } from 'vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import ErFormSection from '@/Components/Ui/ErFormSection.vue'
import ErFormField from '@/Components/Ui/ErFormField.vue'
import ErInput from '@/Components/Ui/ErInput.vue'
import ErSelect from '@/Components/Ui/ErSelect.vue'
import ErButton from '@/Components/Ui/ErButton.vue'
import ConfirmModal from '@/Components/Ui/ConfirmModal.vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'

const props = defineProps({
    user: { type: Object, default: null },
})

const isEditing = !!props.user

const form = useForm({
    name: props.user?.name ?? '',
    email: props.user?.email ?? '',
    password: '',
    password_confirmation: '',
    role: props.user?.role ?? 'viewer',
})

const showDeleteConfirm = ref(false)

function destroy() {
    showDeleteConfirm.value = true
}

function doDestroy() {
    showDeleteConfirm.value = false
    router.delete(route('users.destroy', props.user.id))
}

function submit() {
    if (isEditing) {
        form.put(route('users.update', props.user.id))
    } else {
        form.post(route('users.store'))
    }
}
</script>

<template>
    <Head :title="isEditing ? 'Редактировать пользователя' : 'Новый пользователь'" />
    <AuthenticatedLayout :title="isEditing ? 'Редактировать пользователя' : 'Новый пользователь'">
        <template #breadcrumbs>
            <Link :href="route('dashboard')">Главная</Link>
            <span class="sep">›</span>
            <Link :href="route('users.index')">Пользователи</Link>
            <span class="sep">›</span>
            <span>{{ user ? user.name : 'Новый пользователь' }}</span>
        </template>
        <form @submit.prevent="submit" style="max-width: 700px;">
            <ErFormSection title="Данные пользователя">
                <ErFormField label="Имя" required>
                    <ErInput v-model="form.name" placeholder="Иван Иванов" long />
                    <span v-if="form.errors.name" class="field-err">{{ form.errors.name }}</span>
                </ErFormField>

                <ErFormField label="Email" required>
                    <ErInput v-model="form.email" type="email" placeholder="user@example.com" long />
                    <span v-if="form.errors.email" class="field-err">{{ form.errors.email }}</span>
                </ErFormField>

                <ErFormField label="Роль" required>
                    <ErSelect v-model="form.role">
                        <option value="admin">Администратор</option>
                        <option value="editor">Редактор</option>
                        <option value="viewer">Наблюдатель</option>
                    </ErSelect>
                    <span v-if="form.errors.role" class="field-err">{{ form.errors.role }}</span>
                </ErFormField>
            </ErFormSection>

            <ErFormSection :title="isEditing ? 'Смена пароля' : 'Пароль'">
                <ErFormField label="Пароль" :required="!isEditing"
                    :hint="isEditing ? 'Оставьте пустым, чтобы не менять' : ''">
                    <ErInput v-model="form.password" type="password" long />
                    <span v-if="form.errors.password" class="field-err">{{ form.errors.password }}</span>
                </ErFormField>

                <ErFormField v-if="form.password" label="Подтверждение">
                    <ErInput v-model="form.password_confirmation" type="password" long />
                </ErFormField>
            </ErFormSection>

            <div class="form-actions">
                <ErButton variant="primary" type="submit" :disabled="form.processing">
                    {{ isEditing ? 'Сохранить' : 'Создать' }}
                </ErButton>
                <ErButton :as="'a'" :href="route('users.index')">Отмена</ErButton>
                <ErButton v-if="isEditing" variant="danger" type="button" style="margin-left: auto;" @click="destroy">
                    Удалить
                </ErButton>
            </div>
        </form>
        <ConfirmModal
            :show="showDeleteConfirm"
            title="Удалить пользователя?"
            :message="`Пользователь «${user.name}» будет удалён без возможности восстановления.`"
            confirm-label="Удалить"
            variant="danger"
            @confirm="doDestroy"
            @cancel="showDeleteConfirm = false"
        />
    </AuthenticatedLayout>
</template>

<style scoped>
.field-err { font-size: 11px; color: var(--red); }
.form-actions { display: flex; gap: 8px; margin-top: 4px; }
</style>
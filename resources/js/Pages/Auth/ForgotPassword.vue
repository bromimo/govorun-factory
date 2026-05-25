<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue'
import ErButton from '@/Components/Ui/ErButton.vue'
import { Head, useForm } from '@inertiajs/vue3'

defineProps({
    status: {
        type: String,
    },
})

const form = useForm({
    email: '',
})

const submit = () => {
    form.post(route('password.email'))
}
</script>

<template>
    <GuestLayout>
        <Head title="Восстановление пароля" />

        <div class="login-card">
            <div class="login-title">Восстановление пароля</div>

            <div v-if="status" class="login-status">{{ status }}</div>

            <form @submit.prevent="submit">
                <div class="er-form">
                    <div class="er-fset-h">Введите email для сброса пароля</div>
                    <div class="er-fset-b">
                        <div class="fld">
                            <label class="fld-l">Email <span class="req">*</span></label>
                            <div class="fld-c">
                                <input v-model="form.email" type="email" class="er-inp" autocomplete="username" autofocus required />
                                <div v-if="form.errors.email" class="fld-err">{{ form.errors.email }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="er-form-acts">
                        <ErButton variant="primary" type="submit" :disabled="form.processing">Отправить ссылку</ErButton>
                    </div>
                </div>
            </form>
        </div>
    </GuestLayout>
</template>

<style scoped>
.login-card { width: 420px; }
.login-title { font-size: 18px; font-weight: 600; color: var(--ink); margin-bottom: 16px; letter-spacing: -.01em; }
.login-status { background: var(--green-soft); border: 1px solid #a8d090; color: var(--green); padding: 6px 10px; border-radius: var(--r-md); font-size: 12px; margin-bottom: 12px; }

.er-form { background: var(--surface); border: 1px solid var(--bdr); border-radius: var(--r-md); }
.er-fset-h { padding: 7px 12px; font-size: 12px; font-weight: 600; color: var(--ink); background: linear-gradient(180deg, #f4f6f8 0%, #e8ecf0 100%); border-bottom: 1px solid var(--bdr); border-radius: var(--r-md) var(--r-md) 0 0; }
.er-fset-b { padding: 12px 16px; }
.er-form-acts { padding: 10px 16px; background: linear-gradient(180deg, #f4f6f8 0%, #e8ecf0 100%); border-top: 1px solid var(--bdr); display: flex; align-items: center; gap: 8px; border-radius: 0 0 var(--r-md) var(--r-md); }

.fld { display: grid; grid-template-columns: 110px 1fr; gap: 10px; padding: 5px 0; align-items: start; }
.fld-l { padding-top: 5px; font-size: 12px; color: var(--ink-2); text-align: right; font-weight: 500; }
.fld-c { display: flex; flex-direction: column; gap: 3px; }
.fld-err { font-size: 11px; color: var(--red); }
.req { color: var(--red); }

.er-inp { height: 26px; padding: 0 8px; border: 1px solid var(--bdr-d); border-radius: var(--r-sm); background: #fff; color: var(--ink); font-size: 12px; font-family: var(--font); width: 100%; box-shadow: inset 0 1px 1px rgba(0,0,0,.06); }
.er-inp:focus { outline: none; border-color: var(--blue); box-shadow: 0 0 0 2px rgba(58,114,196,.2); }
</style>
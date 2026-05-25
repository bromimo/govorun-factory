<script setup>
import { ref } from 'vue'
import ErButton from '@/Components/Ui/ErButton.vue'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'

defineProps({ canResetPassword: Boolean, status: String })

const showPassword = ref(false)

const form = useForm({
    email: '',
    password: '',
    remember: false,
})

function submit() {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    })
}
</script>

<template>
    <GuestLayout>
        <Head title="Вход" />

        <div class="login-card">
            <div class="login-title">Вход в систему</div>

            <div v-if="status" class="login-status">{{ status }}</div>

            <form @submit.prevent="submit">
                <div class="er-form">
                    <div class="er-fset-h">Учётные данные</div>
                    <div class="er-fset-b">
                        <div class="fld">
                            <label class="fld-l">Email <span class="req">*</span></label>
                            <div class="fld-c">
                                <input v-model="form.email" type="email" class="er-inp" autocomplete="username" autofocus required />
                                <div v-if="form.errors.email" class="fld-err">{{ form.errors.email }}</div>
                            </div>
                        </div>
                        <div class="fld">
                            <label class="fld-l">Пароль <span class="req">*</span></label>
                            <div class="fld-c">
                                <div class="inp-wrap">
                                    <input
                                        v-model="form.password"
                                        :type="showPassword ? 'text' : 'password'"
                                        class="er-inp"
                                        autocomplete="current-password"
                                        required
                                    />
                                    <button
                                        type="button"
                                        class="eye-btn"
                                        :aria-label="showPassword ? 'Скрыть пароль' : 'Показать пароль'"
                                        :aria-pressed="showPassword"
                                        @click="showPassword = !showPassword"
                                    >
                                        <svg v-if="!showPassword" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                        <svg v-else width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                                            <line x1="1" y1="1" x2="23" y2="23"/>
                                        </svg>
                                    </button>
                                </div>
                                <div v-if="form.errors.password" class="fld-err">{{ form.errors.password }}</div>
                            </div>
                        </div>
                        <div class="fld">
                            <div class="fld-l"></div>
                            <label class="fld-check">
                                <input v-model="form.remember" type="checkbox" />
                                Запомнить меня
                            </label>
                        </div>
                    </div>
                    <div class="er-form-acts">
                        <ErButton variant="primary" type="submit" :disabled="form.processing">Войти</ErButton>
                        <ErButton type="button" @click="form.reset()">Очистить</ErButton>
                        <Link v-if="canResetPassword" :href="route('password.request')" class="reset-link">
                            Забыли пароль?
                        </Link>
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
.fld-check { display: flex; align-items: center; gap: 6px; font-size: 12px; color: var(--ink-2); cursor: pointer; }
.req { color: var(--red); }
.reset-link { margin-left: auto; font-size: 11px; color: var(--blue); text-decoration: none; }
.reset-link:hover { text-decoration: underline; }

.er-inp { height: 26px; padding: 0 8px; border: 1px solid var(--bdr-d); border-radius: var(--r-sm); background: #fff; color: var(--ink); font-size: 12px; font-family: var(--font); width: 100%; box-shadow: inset 0 1px 1px rgba(0,0,0,.06); }
.er-inp:focus { outline: none; border-color: var(--blue); box-shadow: 0 0 0 2px rgba(58,114,196,.2); }

.inp-wrap { position: relative; }
.inp-wrap .er-inp { padding-right: 28px; }
.eye-btn {
    position: absolute;
    right: 4px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    cursor: pointer;
    color: var(--ink-4);
    padding: 2px;
    display: flex;
    align-items: center;
    border-radius: 3px;
    line-height: 1;
}
.eye-btn:hover { color: var(--blue); }
.eye-btn:focus-visible { outline: 2px solid var(--blue); outline-offset: 1px; }
</style>
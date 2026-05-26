<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BotSidebar from '@/Components/Bots/BotSidebar.vue';
import ErButton from '@/Components/Ui/ErButton.vue';
import { useToast } from '@/composables/useToast';

const props = defineProps({
    bot: Object,
    can: Object,
});

const toast = useToast();

const EVENT_TYPES = [
    { key: 'message', label: 'Сообщения' },
    { key: 'subscribed', label: 'Подписка' },
    { key: 'unsubscribed', label: 'Отписка' },
    { key: 'conversation_started', label: 'Начало диалога' },
    { key: 'delivered', label: 'Доставлено' },
    { key: 'seen', label: 'Прочитано' },
    { key: 'failed', label: 'Ошибка доставки' },
];

const profileSource = props.bot.messenger_config?.viber?.profile ?? {};

const form = useForm({
    profile: {
        sender_name: profileSource.sender_name ?? '',
        public_account_uri: profileSource.public_account_uri ?? '',
        event_types: profileSource.event_types ?? EVENT_TYPES.map(e => e.key),
        avatar_path: profileSource.avatar_path ?? null,
    },
});

const avatarVersion = ref(Date.now());
const avatarBusy = ref(false);
const avatarError = ref('');

const avatarUrl = computed(() => {
    if (!form.profile.avatar_path) {
        return null;
    }
    return `${route('bots.viber.avatar.show', props.bot.id)}?v=${avatarVersion.value}`;
});

function toggleEvent(key) {
    const set = new Set(form.profile.event_types);
    if (set.has(key)) {
        set.delete(key);
    } else {
        set.add(key);
    }
    form.profile.event_types = Array.from(set);
}

function uploadAvatar(event) {
    const file = event.target.files?.[0];
    if (!file) {
        return;
    }

    avatarError.value = '';
    avatarBusy.value = true;

    router.post(
        route('bots.viber.avatar.upload', props.bot.id),
        { file },
        {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                const newPath = props.bot.messenger_config?.viber?.profile?.avatar_path ?? null;
                form.profile.avatar_path = newPath;
                avatarVersion.value = Date.now();
            },
            onError: (errors) => {
                avatarError.value = errors.file ?? 'Не удалось загрузить файл';
            },
            onFinish: () => {
                avatarBusy.value = false;
                event.target.value = '';
            },
        },
    );
}

function deleteAvatar() {
    avatarBusy.value = true;
    avatarError.value = '';

    router.delete(route('bots.viber.avatar.delete', props.bot.id), {
        preserveScroll: true,
        onSuccess: () => {
            form.profile.avatar_path = null;
        },
        onFinish: () => {
            avatarBusy.value = false;
        },
    });
}

function save() {
    form.put(route('bots.viber.profile.update', props.bot.id), {
        preserveScroll: true,
        onSuccess: () => toast.success('Профиль сохранён'),
    });
}

function goBack() {
    router.visit(route('bots.edit', props.bot.id), { data: { tab: 'messengers' } });
}

function goToTab(key) {
    router.visit(route('bots.edit', props.bot.id), { data: { tab: key } });
}
</script>

<template>
    <Head :title="`Профиль Viber — ${bot.name}`" />
    <AuthenticatedLayout :title="`Профиль Viber — ${bot.name}`">
        <template #breadcrumbs>
            <Link :href="route('dashboard')">Главная</Link>
            <span class="sep">›</span>
            <Link :href="route('bots.edit', bot.id)">{{ bot.name }}</Link>
            <span class="sep">›</span>
            <Link :href="route('bots.edit', bot.id) + '?tab=messengers'">Мессенджеры</Link>
            <span class="sep">›</span>
            <span>Viber</span>
            <span class="sep">›</span>
            <span>Настройки профиля</span>
        </template>

        <template #sidebar>
            <BotSidebar :bot="bot" active-key="messengers" :on-tab-change="goToTab" />
        </template>

        <form @submit.prevent="save" class="vp-page">
            <div class="vp-body">
                <div class="vp-notice">
                    Имя и аватар попадают в каждое исходящее сообщение Viber как
                    <code>sender.name</code>/<code>sender.avatar</code>.
                    Сам публичный аккаунт настраивается в кабинете Viber Public Account.
                </div>

                <div class="vp-field">
                    <div class="vp-field-hdr">
                        <label class="vp-lbl">Имя отправителя</label>
                        <span class="vp-cnt">{{ form.profile.sender_name.length }}/28</span>
                    </div>
                    <input v-model="form.profile.sender_name" type="text" maxlength="28" class="vp-inp" />
                    <p v-if="form.errors['profile.sender_name']" class="vp-err">
                        {{ form.errors['profile.sender_name'] }}
                    </p>
                </div>

                <div class="vp-field">
                    <label class="vp-lbl">Аватар</label>
                    <div class="vp-photo-box">
                        <div v-if="form.profile.avatar_path" class="vp-photo-row">
                            <img :src="avatarUrl" alt="Аватар" class="vp-photo-preview" />
                            <div class="vp-photo-info">
                                <p class="vp-hint">
                                    Файл загружен.
                                    <button type="button" @click="deleteAvatar" :disabled="avatarBusy" class="vp-del-btn">
                                        Удалить
                                    </button>
                                </p>
                                <p class="vp-hint">Чтобы заменить — удалите текущий и загрузите новый.</p>
                            </div>
                        </div>
                        <div v-else>
                            <input type="file" accept="image/jpeg,image/png"
                                :disabled="avatarBusy" @change="uploadAvatar" class="vp-file-inp" />
                            <p class="vp-hint" style="margin-top:6px">
                                JPG или PNG до 10 MB. Viber требует публичный URL картинки —
                                после экспорта она попадёт в <code>storage/app/public/viber-avatar.&lt;ext&gt;</code>,
                                URL формируется автоматически из <code>APP_URL</code>.
                            </p>
                        </div>
                        <p v-if="avatarError" class="vp-err" style="margin-top:6px">{{ avatarError }}</p>
                    </div>
                </div>

                <div class="vp-field">
                    <div class="vp-field-hdr">
                        <label class="vp-lbl">URI публичного аккаунта</label>
                    </div>
                    <div class="vp-uri-row">
                        <span class="vp-uri-prefix">chats.viber.com/</span>
                        <input v-model="form.profile.public_account_uri" type="text" maxlength="64"
                               class="vp-inp vp-uri-inp" placeholder="mybot" />
                    </div>
                    <p v-if="form.errors['profile.public_account_uri']" class="vp-err">
                        {{ form.errors['profile.public_account_uri'] }}
                    </p>
                </div>

                <div class="vp-field">
                    <label class="vp-lbl">Подписка на события webhook</label>
                    <div class="vp-events-box">
                        <label v-for="ev in EVENT_TYPES" :key="ev.key" class="vp-ev-item">
                            <input type="checkbox" :checked="form.profile.event_types.includes(ev.key)"
                                   @change="toggleEvent(ev.key)" />
                            <span>{{ ev.label }}</span>
                            <code class="vp-ev-key">{{ ev.key }}</code>
                        </label>
                        <p v-if="form.errors['profile.event_types']" class="vp-err">
                            {{ form.errors['profile.event_types'] }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="vp-foot">
                <p class="vp-foot-note">
                    Изменения сохраняются в фабрике. Webhook регистрируется в Viber после деплоя через
                    <code>php artisan messenger:webhook:install viber</code>.
                </p>
                <div class="vp-foot-acts">
                    <ErButton type="button" @click="goBack">Назад</ErButton>
                    <ErButton variant="primary" type="submit" :disabled="form.processing || !can.update">
                        Сохранить профиль
                    </ErButton>
                </div>
            </div>
        </form>
    </AuthenticatedLayout>
</template>

<style scoped>
.vp-page { display: flex; flex-direction: column; min-height: 0; }
.vp-body { flex: 1; display: flex; flex-direction: column; gap: 16px; min-height: 0; }
.vp-foot {
    flex-shrink: 0; margin-top: 16px; padding: 10px 0 0; border-top: 1px solid var(--bdr);
    display: flex; align-items: flex-start; justify-content: space-between; gap: 12px;
}
.vp-foot-note { font-size: 11px; color: var(--ink-3); flex: 1; margin: 0; padding-top: 2px; }
.vp-foot-note code { font-family: var(--mono); background: var(--surface-3); padding: 1px 3px; border-radius: 2px; }
.vp-foot-acts { display: flex; gap: 8px; flex-shrink: 0; }

.vp-notice {
    background: var(--violet-soft, #f3e8ff);
    border: 1px solid #a78bfa;
    border-radius: var(--r-sm);
    padding: 8px 12px;
    font-size: 12px;
    color: #6d28d9;
}
.vp-notice code { font-family: var(--mono); }

.vp-field { display: flex; flex-direction: column; gap: 4px; }
.vp-field-hdr { display: flex; align-items: center; justify-content: space-between; }
.vp-lbl { font-size: 11px; font-weight: 600; color: var(--ink-2); }
.vp-cnt { font-size: 11px; color: var(--ink-4); font-family: var(--mono); }

.vp-inp {
    width: 100%; padding: 5px 8px; height: 26px;
    border: 1px solid var(--bdr-d); border-radius: var(--r-sm);
    background: #fff; color: var(--ink); font-size: 12px; font-family: var(--font);
    box-shadow: inset 0 1px 1px rgba(0,0,0,.05);
    transition: border-color .12s, box-shadow .12s;
    box-sizing: border-box;
}
.vp-inp:focus { outline: none; border-color: var(--blue); box-shadow: 0 0 0 2px rgba(58,114,196,.18); }

.vp-uri-row { display: flex; align-items: stretch; }
.vp-uri-prefix {
    display: inline-flex; align-items: center; padding: 0 8px;
    border: 1px solid var(--bdr-d); border-right: none; border-radius: var(--r-sm) 0 0 var(--r-sm);
    background: var(--surface-2); font-size: 12px; color: var(--ink-3); white-space: nowrap;
}
.vp-uri-inp { border-radius: 0 var(--r-sm) var(--r-sm) 0; }

.vp-hint { font-size: 11px; color: var(--ink-3); margin: 0; }
.vp-hint code { font-family: var(--mono); background: var(--surface-3); padding: 1px 3px; border-radius: 2px; }
.vp-err  { font-size: 11px; color: var(--red); margin: 0; }

.vp-photo-box {
    border: 1px solid var(--bdr); border-radius: var(--r-sm); padding: 10px 12px; background: var(--surface-2);
}
.vp-photo-row { display: flex; align-items: flex-start; gap: 12px; }
.vp-photo-preview {
    width: 96px; height: 96px; border-radius: var(--r-md);
    object-fit: cover; background: var(--surface-3); border: 1px solid var(--bdr);
}
.vp-photo-info { flex: 1; display: flex; flex-direction: column; gap: 4px; }
.vp-del-btn { background: none; border: none; color: var(--red); cursor: pointer; padding: 0; font-size: 12px; font-family: var(--font); }
.vp-del-btn:hover { text-decoration: underline; }
.vp-del-btn:disabled { opacity: .5; cursor: not-allowed; }
.vp-file-inp { width: 100%; font-size: 12px; color: var(--ink-2); font-family: var(--font); }

.vp-events-box {
    border: 1px solid var(--bdr); border-radius: var(--r-sm); padding: 10px 12px; background: var(--surface-2);
    display: flex; flex-direction: column; gap: 6px;
}
.vp-ev-item { display: flex; align-items: center; gap: 8px; font-size: 12px; cursor: pointer; }
.vp-ev-key { font-family: var(--mono); color: var(--ink-3); font-size: 11px; margin-left: auto; }
</style>
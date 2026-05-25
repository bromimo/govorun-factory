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

const profileSource = props.bot.messenger_config?.telegram?.profile ?? {};

const form = useForm({
    profile: {
        name: profileSource.name ?? '',
        short_description: profileSource.short_description ?? '',
        description: profileSource.description ?? '',
        photo_path: profileSource.photo_path ?? null,
    },
});

const photoVersion = ref(Date.now());
const photoBusy = ref(false);
const photoError = ref('');

const photoUrl = computed(() => {
    if (!form.profile.photo_path) {
        return null;
    }
    return `${route('bots.profile-photo.show', props.bot.id)}?v=${photoVersion.value}`;
});

const photoIsVideo = computed(() => {
    return (form.profile.photo_path ?? '').toLowerCase().endsWith('.mp4');
});

const commandRoutes = computed(() => {
    const routes = props.bot.routes ?? [];
    return routes
        .filter(r => r.type === 'command')
        .map(r => ({
            command: (r.match ?? '').replace(/^\//, ''),
            description: r.description ?? '',
        }))
        .filter(r => r.command !== '');
});

function uploadPhoto(event) {
    const file = event.target.files?.[0];
    if (!file) {
        return;
    }

    photoError.value = '';
    photoBusy.value = true;

    router.post(
        route('bots.profile-photo.upload', props.bot.id),
        { file },
        {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                const newPath = props.bot.messenger_config?.telegram?.profile?.photo_path ?? null;
                form.profile.photo_path = newPath;
                photoVersion.value = Date.now();
            },
            onError: (errors) => {
                photoError.value = errors.file ?? 'Не удалось загрузить файл';
            },
            onFinish: () => {
                photoBusy.value = false;
                event.target.value = '';
            },
        },
    );
}

function deletePhoto() {
    photoBusy.value = true;
    photoError.value = '';

    router.delete(route('bots.profile-photo.delete', props.bot.id), {
        preserveScroll: true,
        onSuccess: () => {
            form.profile.photo_path = null;
        },
        onFinish: () => {
            photoBusy.value = false;
        },
    });
}

function save() {
    form.put(route('bots.telegram.profile.update', props.bot.id), {
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
    <Head :title="`Профиль Telegram — ${bot.name}`" />
    <AuthenticatedLayout :title="`Профиль Telegram — ${bot.name}`">
        <template #breadcrumbs>
            <Link :href="route('dashboard')">Главная</Link>
            <span class="sep">›</span>
            <Link :href="route('bots.edit', bot.id)">{{ bot.name }}</Link>
            <span class="sep">›</span>
            <Link :href="route('bots.edit', bot.id) + '?tab=messengers'">Мессенджеры</Link>
            <span class="sep">›</span>
            <span>Telegram</span>
            <span class="sep">›</span>
            <span>Настройки профиля</span>
        </template>

        <template #sidebar>
            <BotSidebar :bot="bot" active-key="messengers" :on-tab-change="goToTab" />
        </template>

        <form @submit.prevent="save" class="tp-page">
            <div class="tp-body">
                <div class="tp-notice">
                    При синхронизации <strong>пустые поля очистят</strong> соответствующие значения в Telegram.
                    Если что-то уже настроено через @BotFather — перенесите сюда перед первым
                    <code>bot:profile-sync</code>.
                </div>

                <div class="tp-field">
                    <div class="tp-field-hdr">
                        <label class="tp-lbl">Имя бота</label>
                        <span class="tp-cnt">{{ form.profile.name.length }}/64</span>
                    </div>
                    <input v-model="form.profile.name" type="text" maxlength="64" class="tp-inp" />
                    <p v-if="form.errors['profile.name']" class="tp-err">
                        {{ form.errors['profile.name'] }}
                    </p>
                </div>

                <div class="tp-field">
                    <div class="tp-field-hdr">
                        <label class="tp-lbl">Короткое описание (about)</label>
                        <span class="tp-cnt">{{ form.profile.short_description.length }}/120</span>
                    </div>
                    <textarea v-model="form.profile.short_description" rows="2" maxlength="120" class="tp-tx" />
                    <p v-if="form.errors['profile.short_description']" class="tp-err">
                        {{ form.errors['profile.short_description'] }}
                    </p>
                </div>

                <div class="tp-field">
                    <div class="tp-field-hdr">
                        <label class="tp-lbl">Описание</label>
                        <span class="tp-cnt">{{ form.profile.description.length }}/512</span>
                    </div>
                    <textarea v-model="form.profile.description" rows="4" maxlength="512" class="tp-tx" />
                    <p v-if="form.errors['profile.description']" class="tp-err">
                        {{ form.errors['profile.description'] }}
                    </p>
                </div>

                <div class="tp-field">
                    <label class="tp-lbl">Аватар</label>
                    <div class="tp-photo-box">
                        <div v-if="form.profile.photo_path" class="tp-photo-row">
                            <video v-if="photoIsVideo" :src="photoUrl"
                                class="tp-photo-preview" muted loop autoplay playsinline />
                            <img v-else :src="photoUrl" alt="Аватар" class="tp-photo-preview" />
                            <div class="tp-photo-info">
                                <p class="tp-hint">
                                    Файл загружен.
                                    <button type="button" @click="deletePhoto" :disabled="photoBusy" class="tp-del-btn">
                                        Удалить
                                    </button>
                                </p>
                                <p class="tp-hint">Чтобы заменить — удалите текущий и загрузите новый.</p>
                            </div>
                        </div>
                        <div v-else>
                            <input type="file" accept="image/jpeg,image/png,video/mp4"
                                :disabled="photoBusy" @change="uploadPhoto" class="tp-file-inp" />
                            <p class="tp-hint" style="margin-top:6px">
                                JPG/PNG или MP4 до 10 MB. Видео автоматически нормализуется под Telegram
                                (640×640, ≤5 сек, без аудио). BotFather UI принимает только фото —
                                анимированный аватар встанет через <code>bot:profile-sync</code>.
                            </p>
                        </div>
                        <p v-if="photoError" class="tp-err" style="margin-top:6px">{{ photoError }}</p>
                    </div>
                </div>

                <div class="tp-field">
                    <label class="tp-lbl">Команды меню</label>
                    <div class="tp-cmd-box">
                        <p class="tp-hint">
                            Список собирается автоматически из маршрутов типа <strong>command</strong>
                            с заполненным описанием. Управлять командами — на вкладке «Маршруты».
                        </p>
                        <ul v-if="commandRoutes.length" class="tp-cmd-list">
                            <li v-for="cmd in commandRoutes" :key="cmd.command" class="tp-cmd-item">
                                <code>/{{ cmd.command }}</code>
                                <span v-if="cmd.description" class="tp-cmd-desc">— {{ cmd.description }}</span>
                                <span v-else class="tp-cmd-warn">— без описания, в меню не попадёт</span>
                            </li>
                        </ul>
                        <p v-else class="tp-hint" style="margin-top:6px;font-style:italic">Команд-маршрутов пока нет.</p>
                    </div>
                </div>
            </div>

            <div class="tp-foot">
                <p class="tp-foot-note">
                    Изменения сохраняются в фабрике. В Telegram попадут после
                    <code>php artisan bot:profile-sync</code> в развёрнутом боте.
                </p>
                <div class="tp-foot-acts">
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
.tp-page {
    display: flex;
    flex-direction: column;
    min-height: 0;
}
.tp-body {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 16px;
    min-height: 0;
}
.tp-foot {
    flex-shrink: 0;
    margin-top: 16px;
    padding: 10px 0 0;
    border-top: 1px solid var(--bdr);
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
}
.tp-foot-note {
    font-size: 11px;
    color: var(--ink-3);
    flex: 1;
    margin: 0;
    padding-top: 2px;
}
.tp-foot-note code { font-family: var(--mono); background: var(--surface-3); padding: 1px 3px; border-radius: 2px; }
.tp-foot-acts { display: flex; gap: 8px; flex-shrink: 0; }

.tp-notice {
    background: var(--orange-soft);
    border: 1px solid #e8a850;
    border-radius: var(--r-sm);
    padding: 8px 12px;
    font-size: 12px;
    color: var(--orange);
}
.tp-notice code { font-family: var(--mono); }

.tp-field { display: flex; flex-direction: column; gap: 4px; }
.tp-field-hdr { display: flex; align-items: center; justify-content: space-between; }
.tp-lbl { font-size: 11px; font-weight: 600; color: var(--ink-2); }
.tp-cnt { font-size: 11px; color: var(--ink-4); font-family: var(--mono); }

.tp-inp, .tp-tx {
    width: 100%;
    padding: 5px 8px;
    border: 1px solid var(--bdr-d);
    border-radius: var(--r-sm);
    background: #fff;
    color: var(--ink);
    font-size: 12px;
    font-family: var(--font);
    box-shadow: inset 0 1px 1px rgba(0,0,0,.05);
    transition: border-color .12s, box-shadow .12s;
    box-sizing: border-box;
}
.tp-inp { height: 26px; }
.tp-tx { min-height: 60px; line-height: 1.5; resize: vertical; }
.tp-inp:focus, .tp-tx:focus {
    outline: none;
    border-color: var(--blue);
    box-shadow: 0 0 0 2px rgba(58,114,196,.18);
}

.tp-hint { font-size: 11px; color: var(--ink-3); margin: 0; }
.tp-hint code { font-family: var(--mono); background: var(--surface-3); padding: 1px 3px; border-radius: 2px; }
.tp-err  { font-size: 11px; color: var(--red); margin: 0; }

.tp-photo-box {
    border: 1px solid var(--bdr);
    border-radius: var(--r-sm);
    padding: 10px 12px;
    background: var(--surface-2);
}
.tp-photo-row { display: flex; align-items: flex-start; gap: 12px; }
.tp-photo-preview { width: 96px; height: 96px; border-radius: var(--r-md); object-fit: cover; background: var(--surface-3); border: 1px solid var(--bdr); }
.tp-photo-info { flex: 1; display: flex; flex-direction: column; gap: 4px; }
.tp-del-btn { background: none; border: none; color: var(--red); cursor: pointer; padding: 0; font-size: 12px; font-family: var(--font); }
.tp-del-btn:hover { text-decoration: underline; }
.tp-del-btn:disabled { opacity: .5; cursor: not-allowed; }

.tp-file-inp {
    width: 100%;
    font-size: 12px;
    color: var(--ink-2);
    font-family: var(--font);
}

.tp-cmd-box {
    border: 1px solid var(--bdr);
    border-radius: var(--r-sm);
    padding: 10px 12px;
    background: var(--surface-2);
}
.tp-cmd-list { margin: 8px 0 0; padding: 0; list-style: none; display: flex; flex-direction: column; gap: 4px; }
.tp-cmd-item { display: flex; align-items: baseline; gap: 6px; font-size: 12px; }
.tp-cmd-item code { font-family: var(--mono); color: var(--ink); }
.tp-cmd-desc { color: var(--ink-2); }
.tp-cmd-warn { color: var(--orange); font-size: 11px; }
</style>
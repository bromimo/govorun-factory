<script setup>
import { computed, ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import ErButton from '@/Components/Ui/ErButton.vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    bot: Object,
    can: Object,
});

const emit = defineEmits(['close']);

const profileSource = props.bot.messenger_config?.telegram?.profile ?? {};

const form = useForm({
    messenger_config: {
        ...(props.bot.messenger_config ?? {}),
        telegram: {
            ...(props.bot.messenger_config?.telegram ?? { enabled: true }),
            profile: {
                name: profileSource.name ?? '',
                short_description: profileSource.short_description ?? '',
                description: profileSource.description ?? '',
                photo_path: profileSource.photo_path ?? null,
            },
        },
    },
});

const profile = computed(() => form.messenger_config.telegram.profile);
const photoVersion = ref(Date.now());
const photoBusy = ref(false);
const photoError = ref('');

const photoUrl = computed(() => {
    if (!profile.value.photo_path) {
        return null;
    }

    return `${route('bots.profile-photo.show', props.bot.id)}?v=${photoVersion.value}`;
});

const photoIsVideo = computed(() => {
    return (profile.value.photo_path ?? '').toLowerCase().endsWith('.mp4');
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
                profile.value.photo_path = newPath;
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
            profile.value.photo_path = null;
        },
        onFinish: () => {
            photoBusy.value = false;
        },
    });
}

function save() {
    form.put(route('bots.update', props.bot.id), {
        preserveScroll: true,
    });
}

watch(() => form.recentlySuccessful, (val) => {
    if (val && props.show) {
        emit('close');
    }
});
</script>

<template>
    <Modal :show="show" title="Профиль Telegram-бота" max-width="2xl" @close="emit('close')">
        <form @submit.prevent="save" class="tp-form">
            <div class="tp-body">
                <div class="tp-notice">
                    При синхронизации <strong>пустые поля очистят</strong> соответствующие значения в Telegram.
                    Если что-то уже настроено через @BotFather — перенесите сюда перед первым
                    <code>bot:profile-sync</code>.
                </div>

                <div class="tp-field">
                    <div class="tp-field-hdr">
                        <label class="tp-lbl">Имя бота</label>
                        <span class="tp-cnt">{{ profile.name.length }}/64</span>
                    </div>
                    <input v-model="profile.name" type="text" maxlength="64" class="tp-inp" />
                    <p v-if="form.errors['messenger_config.telegram.profile.name']" class="tp-err">
                        {{ form.errors['messenger_config.telegram.profile.name'] }}
                    </p>
                </div>

                <div class="tp-field">
                    <div class="tp-field-hdr">
                        <label class="tp-lbl">Короткое описание (about)</label>
                        <span class="tp-cnt">{{ profile.short_description.length }}/120</span>
                    </div>
                    <textarea v-model="profile.short_description" rows="2" maxlength="120" class="tp-tx" />
                    <p v-if="form.errors['messenger_config.telegram.profile.short_description']" class="tp-err">
                        {{ form.errors['messenger_config.telegram.profile.short_description'] }}
                    </p>
                </div>

                <div class="tp-field">
                    <div class="tp-field-hdr">
                        <label class="tp-lbl">Описание</label>
                        <span class="tp-cnt">{{ profile.description.length }}/512</span>
                    </div>
                    <textarea v-model="profile.description" rows="4" maxlength="512" class="tp-tx" />
                    <p v-if="form.errors['messenger_config.telegram.profile.description']" class="tp-err">
                        {{ form.errors['messenger_config.telegram.profile.description'] }}
                    </p>
                </div>

                <div class="tp-field">
                    <label class="tp-lbl">Аватар</label>
                    <div class="tp-photo-box">
                        <div v-if="profile.photo_path" class="tp-photo-row">
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
                    <ErButton type="button" @click="emit('close')">Отмена</ErButton>
                    <ErButton variant="primary" type="submit" :disabled="form.processing">Сохранить профиль</ErButton>
                </div>
            </div>
        </form>
    </Modal>
</template>

<style scoped>
.tp-form {
    display: flex;
    flex-direction: column;
    min-height: 0;
    overflow: hidden;
}
.tp-body {
    flex: 1;
    overflow-y: auto;
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 16px;
    min-height: 0;
}
.tp-foot {
    flex-shrink: 0;
    padding: 10px 14px;
    border-top: 1px solid var(--bdr);
    background: linear-gradient(180deg, #f4f6f8 0%, #e8ecf0 100%);
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
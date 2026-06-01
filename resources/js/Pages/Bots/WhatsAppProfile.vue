<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BotSidebar from '@/Components/Bots/BotSidebar.vue';
import ErButton from '@/Components/Ui/ErButton.vue';
import ErInput from '@/Components/Ui/ErInput.vue';
import ErSelect from '@/Components/Ui/ErSelect.vue';
import ErTextarea from '@/Components/Ui/ErTextarea.vue';
import { useToast } from '@/composables/useToast';

const props = defineProps({
    bot: Object,
    can: Object,
});

const toast = useToast();

const verticals = [
    'UNDEFINED', 'OTHER', 'AUTO', 'BEAUTY', 'APPAREL', 'EDU', 'ENTERTAIN',
    'EVENT_PLAN', 'FINANCE', 'GROCERY', 'GOVT', 'HOTEL', 'HEALTH', 'NONPROFIT',
    'PROF_SERVICES', 'RETAIL', 'TRAVEL', 'RESTAURANT', 'NOT_A_BIZ',
];

const profileSource = props.bot.messenger_config?.whatsapp?.profile ?? {};

const websitesText = ref((profileSource.websites ?? []).join('\n'));

const form = useForm({
    profile: {
        about: profileSource.about ?? '',
        description: profileSource.description ?? '',
        address: profileSource.address ?? '',
        email: profileSource.email ?? '',
        vertical: profileSource.vertical ?? 'UNDEFINED',
        websites: profileSource.websites ?? [],
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
    return `${route('bots.whatsapp.photo.show', props.bot.id)}?v=${photoVersion.value}`;
});

function syncWebsites() {
    form.profile.websites = websitesText.value
        .split('\n')
        .map((s) => s.trim())
        .filter((s) => s.length > 0)
        .slice(0, 2);
}

function uploadPhoto(event) {
    const file = event.target.files?.[0];
    if (!file) {
        return;
    }

    photoError.value = '';
    photoBusy.value = true;

    router.post(
        route('bots.whatsapp.photo.upload', props.bot.id),
        { file },
        {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                const newPath = props.bot.messenger_config?.whatsapp?.profile?.photo_path ?? null;
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

    router.delete(route('bots.whatsapp.photo.delete', props.bot.id), {
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
    syncWebsites();
    form.put(route('bots.whatsapp.profile.update', props.bot.id), {
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
    <Head :title="`Профиль WhatsApp — ${bot.name}`" />
    <AuthenticatedLayout :title="`Профиль WhatsApp — ${bot.name}`">
        <template #breadcrumbs>
            <Link :href="route('dashboard')">Главная</Link>
            <span class="sep">›</span>
            <Link :href="route('bots.edit', bot.id)">{{ bot.name }}</Link>
            <span class="sep">›</span>
            <Link :href="route('bots.edit', bot.id) + '?tab=messengers'">Мессенджеры</Link>
            <span class="sep">›</span>
            <span>WhatsApp</span>
            <span class="sep">›</span>
            <span>Настройки профиля</span>
        </template>

        <template #sidebar>
            <BotSidebar :bot="bot" active-key="messengers" :on-tab-change="goToTab" />
        </template>

        <form @submit.prevent="save" class="vp-page">
            <div class="vp-body">
                <div class="vp-notice">
                    Данные профиля отображаются в карточке бизнес-аккаунта WhatsApp.
                    Они передаются в WhatsApp Cloud API при деплое через
                    <code>php artisan messenger:profile:sync whatsapp</code>.
                </div>

                <div class="vp-field">
                    <div class="vp-field-hdr">
                        <label class="vp-lbl">О компании (about)</label>
                        <span class="vp-cnt">{{ form.profile.about.length }}/139</span>
                    </div>
                    <input
                        v-model="form.profile.about"
                        type="text"
                        maxlength="139"
                        class="vp-inp"
                        :disabled="!can.update"
                    />
                    <p v-if="form.errors['profile.about']" class="vp-err">
                        {{ form.errors['profile.about'] }}
                    </p>
                </div>

                <div class="vp-field">
                    <div class="vp-field-hdr">
                        <label class="vp-lbl">Описание (description)</label>
                        <span class="vp-cnt">{{ form.profile.description.length }}/512</span>
                    </div>
                    <textarea
                        v-model="form.profile.description"
                        maxlength="512"
                        rows="3"
                        class="vp-inp vp-textarea"
                        :disabled="!can.update"
                    ></textarea>
                    <p v-if="form.errors['profile.description']" class="vp-err">
                        {{ form.errors['profile.description'] }}
                    </p>
                </div>

                <div class="vp-field">
                    <div class="vp-field-hdr">
                        <label class="vp-lbl">Адрес</label>
                        <span class="vp-cnt">{{ form.profile.address.length }}/256</span>
                    </div>
                    <input
                        v-model="form.profile.address"
                        type="text"
                        maxlength="256"
                        class="vp-inp"
                        :disabled="!can.update"
                    />
                    <p v-if="form.errors['profile.address']" class="vp-err">
                        {{ form.errors['profile.address'] }}
                    </p>
                </div>

                <div class="vp-field">
                    <div class="vp-field-hdr">
                        <label class="vp-lbl">Email</label>
                    </div>
                    <input
                        v-model="form.profile.email"
                        type="email"
                        maxlength="128"
                        class="vp-inp"
                        placeholder="info@example.com"
                        :disabled="!can.update"
                    />
                    <p v-if="form.errors['profile.email']" class="vp-err">
                        {{ form.errors['profile.email'] }}
                    </p>
                </div>

                <div class="vp-field">
                    <div class="vp-field-hdr">
                        <label class="vp-lbl">Вертикаль (vertical)</label>
                    </div>
                    <select
                        v-model="form.profile.vertical"
                        class="vp-inp vp-select"
                        :disabled="!can.update"
                    >
                        <option v-for="v in verticals" :key="v" :value="v">{{ v }}</option>
                    </select>
                    <p v-if="form.errors['profile.vertical']" class="vp-err">
                        {{ form.errors['profile.vertical'] }}
                    </p>
                </div>

                <div class="vp-field">
                    <div class="vp-field-hdr">
                        <label class="vp-lbl">Веб-сайты (до 2 штук)</label>
                    </div>
                    <textarea
                        v-model="websitesText"
                        rows="2"
                        class="vp-inp vp-textarea"
                        placeholder="https://example.com"
                        :disabled="!can.update"
                    ></textarea>
                    <p class="vp-hint">Каждый сайт — на отдельной строке. Не более двух URL.</p>
                    <p v-if="form.errors['profile.websites']" class="vp-err">
                        {{ form.errors['profile.websites'] }}
                    </p>
                </div>

                <div class="vp-field">
                    <label class="vp-lbl">Фото профиля</label>
                    <div class="vp-photo-box">
                        <div v-if="form.profile.photo_path" class="vp-photo-row">
                            <img :src="photoUrl" alt="Фото профиля" class="vp-photo-preview" />
                            <div class="vp-photo-info">
                                <p class="vp-hint">
                                    Файл загружен.
                                    <button type="button" @click="deletePhoto" :disabled="photoBusy" class="vp-del-btn">
                                        Удалить
                                    </button>
                                </p>
                                <p class="vp-hint">Чтобы заменить — удалите текущий и загрузите новый.</p>
                            </div>
                        </div>
                        <div v-else>
                            <input
                                type="file"
                                accept="image/jpeg,image/png"
                                :disabled="photoBusy"
                                @change="uploadPhoto"
                                class="vp-file-inp"
                            />
                            <p class="vp-hint" style="margin-top:6px">
                                JPG или PNG до 5 MB. Изображение передаётся в WhatsApp Cloud API при синхронизации профиля.
                            </p>
                        </div>
                        <p v-if="photoError" class="vp-err" style="margin-top:6px">{{ photoError }}</p>
                    </div>
                </div>
            </div>

            <div class="vp-foot">
                <p class="vp-foot-note">
                    Изменения сохраняются в фабрике. Синхронизация с WhatsApp выполняется после деплоя через
                    <code>php artisan messenger:profile:sync whatsapp</code>.
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
    background: var(--green-soft, #dcfce7);
    border: 1px solid #86efac;
    border-radius: var(--r-sm);
    padding: 8px 12px;
    font-size: 12px;
    color: #166534;
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
.vp-inp:disabled { background: var(--surface-2); color: var(--ink-3); cursor: not-allowed; }

.vp-textarea {
    height: auto;
    padding: 6px 8px;
    resize: vertical;
    line-height: 1.4;
}

.vp-select {
    cursor: pointer;
}

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
</style>
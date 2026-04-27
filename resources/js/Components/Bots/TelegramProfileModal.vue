<script setup>
import { computed, ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
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
    if (! profile.value.photo_path) {
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
    if (! file) {
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
    <Modal :show="show" max-width="2xl" @close="emit('close')">
        <form @submit.prevent="save" class="flex max-h-[85vh] flex-col">
            <div class="border-b border-gray-200 px-6 py-4">
                <h3 class="text-lg font-medium text-gray-900">Профиль Telegram-бота</h3>
            </div>

            <div class="flex-1 overflow-y-auto px-6 py-4">
                <div class="rounded-md border border-yellow-300 bg-yellow-50 p-3 text-sm text-yellow-800">
                    При синхронизации <strong>пустые поля очистят</strong> соответствующие значения в Telegram.
                    Если что-то уже настроено через @BotFather — перенесите сюда перед первым
                    <code>bot:profile-sync</code>.
                </div>

                <div class="mt-6 space-y-5">
                    <div>
                        <div class="flex items-center justify-between">
                            <label class="block text-sm font-medium text-gray-700">Имя бота</label>
                            <span class="text-xs text-gray-500">{{ profile.name.length }}/64</span>
                        </div>
                        <input v-model="profile.name" type="text" maxlength="64"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                        <p v-if="form.errors['messenger_config.telegram.profile.name']"
                            class="mt-1 text-sm text-red-600">
                            {{ form.errors['messenger_config.telegram.profile.name'] }}
                        </p>
                    </div>

                    <div>
                        <div class="flex items-center justify-between">
                            <label class="block text-sm font-medium text-gray-700">
                                Короткое описание (about)
                            </label>
                            <span class="text-xs text-gray-500">
                                {{ profile.short_description.length }}/120
                            </span>
                        </div>
                        <textarea v-model="profile.short_description" rows="2" maxlength="120"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                        <p v-if="form.errors['messenger_config.telegram.profile.short_description']"
                            class="mt-1 text-sm text-red-600">
                            {{ form.errors['messenger_config.telegram.profile.short_description'] }}
                        </p>
                    </div>

                    <div>
                        <div class="flex items-center justify-between">
                            <label class="block text-sm font-medium text-gray-700">Описание</label>
                            <span class="text-xs text-gray-500">{{ profile.description.length }}/512</span>
                        </div>
                        <textarea v-model="profile.description" rows="4" maxlength="512"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                        <p v-if="form.errors['messenger_config.telegram.profile.description']"
                            class="mt-1 text-sm text-red-600">
                            {{ form.errors['messenger_config.telegram.profile.description'] }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Аватар</label>
                        <div class="mt-1 rounded-md border border-gray-200 p-3">
                            <div v-if="profile.photo_path" class="flex items-start gap-3">
                                <video v-if="photoIsVideo" :src="photoUrl"
                                    class="h-32 w-32 rounded-md bg-gray-100 object-cover"
                                    muted loop autoplay playsinline />
                                <img v-else :src="photoUrl" alt="Аватар"
                                    class="h-32 w-32 rounded-md bg-gray-100 object-cover" />
                                <div class="flex-1 space-y-2">
                                    <p class="text-sm text-gray-700">
                                        Файл загружен. <button type="button" @click="deletePhoto"
                                            :disabled="photoBusy"
                                            class="text-red-600 hover:text-red-500 disabled:opacity-50">
                                            Удалить
                                        </button>
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        Чтобы заменить — удалите текущий и загрузите новый.
                                    </p>
                                </div>
                            </div>

                            <div v-else>
                                <input type="file" accept="image/jpeg,image/png,video/mp4"
                                    :disabled="photoBusy" @change="uploadPhoto"
                                    class="block w-full text-sm text-gray-700 file:mr-3 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-indigo-700 hover:file:bg-indigo-100" />
                                <p class="mt-2 text-xs text-gray-500">
                                    JPG/PNG или MP4 до 10 MB. Видео автоматически нормализуется под Telegram
                                    (640×640, ≤5 сек, без аудио). BotFather UI принимает только фото —
                                    анимированный аватар встанет через <code>bot:profile-sync</code>.
                                </p>
                            </div>

                            <p v-if="photoError" class="mt-2 text-sm text-red-600">{{ photoError }}</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Команды меню</label>
                        <div class="mt-1 rounded-md border border-gray-200 bg-gray-50 p-3">
                            <p class="text-xs text-gray-500">
                                Список собирается автоматически из маршрутов типа <strong>command</strong>
                                с заполненным описанием. Управлять командами — на вкладке «Маршруты».
                            </p>
                            <ul v-if="commandRoutes.length" class="mt-2 space-y-1">
                                <li v-for="cmd in commandRoutes" :key="cmd.command"
                                    class="flex items-baseline gap-2 text-sm">
                                    <code class="text-gray-800">/{{ cmd.command }}</code>
                                    <span v-if="cmd.description" class="text-gray-600">— {{ cmd.description }}</span>
                                    <span v-else class="text-amber-600">
                                        — без описания, в меню не попадёт
                                    </span>
                                </li>
                            </ul>
                            <p v-else class="mt-2 text-sm text-gray-500 italic">
                                Команд-маршрутов пока нет.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-200 px-6 py-4">
                <p class="text-xs text-gray-500">
                    Изменения сохраняются в фабрике. В Telegram попадут после
                    <code>php artisan bot:profile-sync</code> в развёрнутом боте.
                </p>
                <div class="mt-3 flex justify-end gap-3">
                    <button type="button" @click="emit('close')"
                        class="rounded-md bg-white px-4 py-2 text-sm font-medium text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50">
                        Отмена
                    </button>
                    <button type="submit" :disabled="form.processing"
                        class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500 disabled:opacity-50">
                        Сохранить профиль
                    </button>
                </div>
            </div>
        </form>
    </Modal>
</template>

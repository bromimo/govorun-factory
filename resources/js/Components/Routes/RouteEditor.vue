<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import BlockList from './BlockList.vue';
import { toCamelCase, toPascalCase, sanitizeIdentifier, identifierWarning } from '@/utils/translit';
import ErButton from '@/Components/Ui/ErButton.vue';
import ErInput from '@/Components/Ui/ErInput.vue';

const props = defineProps({
    botId: Number,
    route: { type: Object, default: null },
    parentId: { type: Number, default: null },
    hasChildren: { type: Boolean, default: false },
    hasFallback: { type: Boolean, default: false },
    flows: { type: Array, default: () => [] },
});

const emit = defineEmits(['close']);

const isEditing = computed(() => !!props.route);
const isNested = computed(() => !!props.parentId);
const isParentPhrase = computed(() => props.hasChildren);
const showHandler = computed(() => !isParentPhrase.value);

const isFallback = computed(() => props.route?.type === 'fallback');

const routeTypes = computed(() => {
    const items = [
        { value: 'command', label: 'Command' },
        { value: 'phrase', label: 'Phrase' },
        { value: 'pattern', label: 'Pattern' },
        { value: 'action', label: 'Action' },
        { value: 'event', label: 'Event' },
        { value: 'media', label: 'Media' },
        { value: 'location', label: 'Location' },
        { value: 'contact', label: 'Contact' },
        { value: 'referral', label: 'Referral' },
        { value: 'fallback', label: 'Fallback' },
    ];

    return items.map(item => {
        if (item.value !== 'fallback') {
            return { ...item, disabled: isFallback.value };
        }

        const disabled = isEditing.value
            ? ! isFallback.value
            : props.hasFallback;

        return {
            ...item,
            disabled,
            label: disabled && props.hasFallback && ! isFallback.value ? 'Fallback (уже есть)' : item.label,
        };
    });
});

const form = useForm({
    parent_id: props.parentId,
    type: props.parentId ? 'phrase' : (props.route?.type ?? 'command'),
    match: props.route?.match ?? '',
    description: props.route?.description ?? '',
    aliases: props.route?.aliases ?? [],
    controller_name: props.route?.controller_name ?? '',
    handler_type: props.route?.handler_type ?? 'controller',
    flow_id: props.route?.flow_id ?? null,
    handler_schema: props.route?.handler_schema ?? { blocks: [] },
    middleware: props.route?.middleware ?? [],
});

const EVENT_TYPES = [
    { value: 'new_chat_members',  label: 'new_chat_members — новый участник в группе' },
    { value: 'left_chat_member',  label: 'left_chat_member — участник покинул группу' },
    { value: 'new_chat_title',    label: 'new_chat_title — изменение названия группы' },
    { value: 'new_chat_photo',    label: 'new_chat_photo — изменение фото группы' },
    { value: 'group_chat_created', label: 'group_chat_created — создание группы' },
];

const showMatch = computed(() => ['command', 'phrase', 'pattern', 'action', 'referral'].includes(form.type));

const showAliases = computed(() => form.type === 'phrase');

const longMatch = computed(() => (form.match?.length ?? 0) > 20);
const showControllerName = computed(() => {
    if (form.type === 'fallback') {
        return false;
    }

    return isParentPhrase.value || isNested.value || (showHandler.value && form.handler_type === 'controller');
});

const autoControllerName = computed(() => {
    const match = form.match?.trim();
    if (!match) return '';
    return isNested.value ? toCamelCase(match) : toPascalCase(match);
});

const controllerNameWarning = ref('');
let controllerNameWarningTimer = null;

function onControllerNameInput(e) {
    const raw = e.target.value;
    const clean = sanitizeIdentifier(raw);
    const warning = identifierWarning(raw);

    if (warning) {
        controllerNameWarning.value = warning;
        clearTimeout(controllerNameWarningTimer);
        controllerNameWarningTimer = setTimeout(() => { controllerNameWarning.value = ''; }, 3000);
    } else {
        controllerNameWarning.value = '';
    }

    form.controller_name = clean;
}

watch(() => form.type, (newType) => {
    if (newType !== 'phrase') {
        form.aliases = [];
    }

    if (newType === 'fallback') {
        form.controller_name = '';
    }

    if (newType === 'event' && !EVENT_TYPES.some(e => e.value === form.match)) {
        form.match = EVENT_TYPES[0].value;
    }
});

function addAlias() {
    form.aliases.push('');
}

function removeAlias(index) {
    form.aliases.splice(index, 1);
}

function submit() {
    const url = isEditing.value
        ? window.route('bot-routes.update', [props.botId, props.route.id])
        : window.route('bot-routes.store', props.botId);

    const method = isEditing.value ? 'put' : 'post';

    form[method](url, { onSuccess: () => emit('close') });
}
</script>

<template>
    <div class="fixed inset-0 z-50 flex justify-end bg-black/30" @click.self="emit('close')">
        <div class="drawer h-full w-full max-w-lg overflow-y-auto">
            <div class="drawer-h">
                <span>{{ isEditing ? 'Редактировать' : 'Новый' }}{{ isNested ? ' вложенный' : '' }} маршрут</span>
                <button @click="emit('close')" class="text-gray-400 hover:text-gray-600">x</button>
            </div>

            <form @submit.prevent="submit" class="space-y-5 p-5">
                <div v-if="!isNested">
                    <label class="block text-sm font-medium text-gray-700">Тип маршрута</label>
                    <select v-model="form.type" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                        <option v-for="rt in routeTypes" :key="rt.value" :value="rt.value" :disabled="rt.disabled">{{ rt.label }}</option>
                    </select>
                </div>

                <div v-if="showMatch">
                    <label class="block text-sm font-medium text-gray-700">Match</label>
                    <ErInput
                        v-model="form.match"
                        class="mt-1"
                        :placeholder="form.type === 'command' ? '/start' : 'hello'"
                    />
                </div>

                <div v-if="form.type === 'event'">
                    <label class="block text-sm font-medium text-gray-700">Событие</label>
                    <select v-model="form.match" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                        <option v-for="et in EVENT_TYPES" :key="et.value" :value="et.value">{{ et.label }}</option>
                    </select>
                </div>

                <div v-if="form.type === 'command'">
                    <label class="block text-sm font-medium text-gray-700">
                        Описание для меню Telegram
                    </label>
                    <ErInput
                        v-model="form.description"
                        class="mt-1"
                        :long="true"
                        placeholder="Запустить бота"
                    />
                    <p class="mt-1 text-xs text-gray-400">
                        Если пусто — команда не попадёт в меню при <code>bot:profile-sync</code>.
                    </p>
                    <p v-if="form.errors.description" class="mt-1 text-xs text-red-600">
                        {{ form.errors.description }}
                    </p>
                </div>

                <div v-if="showAliases">
                    <label class="block text-sm font-medium text-gray-700">Алиасы</label>
                    <div class="mt-1 space-y-2">
                        <div v-for="(alias, index) in form.aliases" :key="index" class="flex gap-2">
                            <ErInput
                                v-model="form.aliases[index]"
                                :long="true"
                                placeholder="Синоним фразы"
                            />
                            <button type="button" @click="removeAlias(index)"
                                class="shrink-0 text-gray-400 hover:text-red-500">
                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    <button type="button" @click="addAlias"
                        class="mt-2 text-xs text-indigo-600 hover:text-indigo-800">
                        + Добавить алиас
                    </button>
                </div>

                <div v-if="showControllerName">
                    <label class="block text-sm font-medium text-gray-700">
                        {{ isNested ? 'Имя метода' : 'Имя контроллера' }}
                    </label>
                    <div class="mt-1 flex gap-2">
                        <input :value="form.controller_name" type="text"
                            @input="onControllerNameInput($event)"
                            class="er-mono-inp w-full rounded-md border-gray-300 text-sm font-mono"
                            :placeholder="autoControllerName || (isNested ? 'method' : 'Controller')" />
                        <button v-if="form.controller_name" type="button" @click="form.controller_name = ''"
                            class="shrink-0 text-gray-400 hover:text-red-500">
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                    <p v-if="isNested" class="mt-1 text-xs text-gray-400">
                        camelCase. Например: <span class="font-mono">manicure</span>
                    </p>
                    <p v-else-if="isParentPhrase" class="mt-1 text-xs text-gray-400">
                        PascalCase, без суффикса Controller. Например: <span class="font-mono">Price</span> → <span class="font-mono">PriceController</span>
                    </p>
                    <p v-else class="mt-1 text-xs text-gray-400">
                        PascalCase, без суффикса Controller
                    </p>
                    <p v-if="longMatch && !form.controller_name" class="mt-1 text-xs text-amber-600">
                        Фраза длинная — рекомендуется задать короткое имя вручную
                    </p>
                    <p v-if="controllerNameWarning" class="mt-1 text-xs text-amber-600">{{ controllerNameWarning }}</p>
                    <p v-if="form.errors.controller_name" class="mt-1 text-xs text-red-600">{{ form.errors.controller_name }}</p>
                </div>

                <template v-if="showHandler">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Обработчик</label>
                        <div class="mt-1 flex rounded-md border border-gray-300 overflow-hidden">
                            <button type="button" @click="form.handler_type = 'controller'"
                                class="flex-1 px-3 py-2 text-sm font-medium"
                                :class="form.handler_type === 'controller' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700'">
                                Controller
                            </button>
                            <button type="button" @click="form.handler_type = 'flow'"
                                class="flex-1 px-3 py-2 text-sm font-medium"
                                :class="form.handler_type === 'flow' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700'">
                                Flow
                            </button>
                        </div>
                    </div>

                    <div v-if="form.handler_type === 'flow'">
                        <label class="block text-sm font-medium text-gray-700">Flow-диалог</label>
                        <select v-model="form.flow_id" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                            <option :value="null">-- Выберите --</option>
                            <option v-for="f in flows" :key="f.id" :value="f.id">{{ f.name }}</option>
                        </select>
                    </div>

                    <div v-if="form.handler_type === 'controller'">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Блоки</label>
                        <BlockList v-model="form.handler_schema.blocks" :bot-id="botId" />
                    </div>
                </template>

                <p v-else class="text-sm text-gray-500 italic">Обработчик задаётся у дочерних маршрутов</p>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Middleware (через запятую)</label>
                    <ErInput
                        :value="form.middleware.join(', ')"
                        @update:modelValue="form.middleware = $event.split(',').map(s => s.trim()).filter(Boolean)"
                        class="mt-1"
                        :long="true"
                        placeholder="auth, throttle"
                    />
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t">
                    <ErButton type="button" @click="emit('close')">
                        Отмена
                    </ErButton>
                    <ErButton variant="primary" type="submit" :disabled="form.processing">
                        {{ isEditing ? 'Сохранить' : 'Создать' }}
                    </ErButton>
                </div>
            </form>
        </div>
    </div>
</template>

<style scoped>
.drawer {
    background: var(--surface);
    border-left: 1px solid var(--bdr);
}
.drawer-h {
    padding: 10px 14px;
    font-size: 13px;
    font-weight: 600;
    background: linear-gradient(180deg, #f4f6f8 0%, #e8ecf0 100%);
    border-bottom: 1px solid var(--bdr);
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.er-mono-inp {
    height: 26px;
    padding: 0 8px;
    border: 1px solid var(--bdr-d);
    border-radius: var(--r-sm);
    background: #fff;
    color: var(--ink);
    font-size: 12px;
    font-family: monospace;
    box-shadow: inset 0 1px 1px rgba(0,0,0,.06);
}
.er-mono-inp:focus {
    outline: none;
    border-color: var(--blue);
    box-shadow: 0 0 0 2px rgba(58,114,196,.2);
}
</style>
<script setup>
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import BlockList from './BlockList.vue';

const props = defineProps({
    botId: Number,
    route: { type: Object, default: null },
    parentId: { type: Number, default: null },
    hasChildren: { type: Boolean, default: false },
    flows: { type: Array, default: () => [] },
});

const emit = defineEmits(['close']);

const isEditing = computed(() => !!props.route);
const isNested = computed(() => !!props.parentId);
const isParentPhrase = computed(() => props.hasChildren);
const showHandler = computed(() => !isParentPhrase.value);

const routeTypes = [
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

const form = useForm({
    parent_id: props.parentId,
    type: props.parentId ? 'phrase' : (props.route?.type ?? 'command'),
    match: props.route?.match ?? '',
    aliases: props.route?.aliases ?? [],
    controller_name: props.route?.controller_name ?? '',
    handler_type: props.route?.handler_type ?? 'controller',
    flow_id: props.route?.flow_id ?? null,
    handler_schema: props.route?.handler_schema ?? { blocks: [] },
    middleware: props.route?.middleware ?? [],
});

const showMatch = computed(() => ['command', 'phrase', 'pattern', 'action', 'referral'].includes(form.type));

const showAliases = computed(() => form.type === 'phrase');

const longMatch = computed(() => (form.match?.length ?? 0) > 20);
const showControllerName = computed(() => isParentPhrase.value || isNested.value || (showHandler.value && form.handler_type === 'controller'));

const translitMap = {
    а:'a',б:'b',в:'v',г:'g',д:'d',е:'e',ё:'yo',ж:'zh',з:'z',и:'i',й:'j',к:'k',
    л:'l',м:'m',н:'n',о:'o',п:'p',р:'r',с:'s',т:'t',у:'u',ф:'f',х:'kh',ц:'ts',
    ч:'ch',ш:'sh',щ:'shch',ъ:'',ы:'y',ь:'',э:'e',ю:'yu',я:'ya',
};

function translit(str) {
    return str.split('').map(c => {
        const lower = c.toLowerCase();
        if (translitMap[lower] !== undefined) {
            const t = translitMap[lower];
            return c === lower ? t : t.charAt(0).toUpperCase() + t.slice(1);
        }
        return c;
    }).join('');
}

const autoControllerName = computed(() => {
    const match = form.match?.trim();
    if (!match) return '';
    const ascii = translit(match);
    const words = ascii.split(/[^a-zA-Z0-9]+/).filter(Boolean);
    if (isNested.value) {
        return words.map((w, i) => i === 0 ? w.toLowerCase() : w.charAt(0).toUpperCase() + w.slice(1).toLowerCase()).join('');
    }
    return words.map(w => w.charAt(0).toUpperCase() + w.slice(1).toLowerCase()).join('');
});

watch(() => form.type, (newType) => {
    if (newType !== 'phrase') {
        form.aliases = [];
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
        <div class="h-full w-full max-w-lg overflow-y-auto bg-white p-6 shadow-xl">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-medium">{{ isEditing ? 'Редактировать' : 'Новый' }}{{ isNested ? ' вложенный' : '' }} маршрут</h3>
                <button @click="emit('close')" class="text-gray-400 hover:text-gray-600">x</button>
            </div>

            <form @submit.prevent="submit" class="space-y-5">
                <div v-if="!isNested">
                    <label class="block text-sm font-medium text-gray-700">Тип маршрута</label>
                    <select v-model="form.type" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                        <option v-for="rt in routeTypes" :key="rt.value" :value="rt.value">{{ rt.label }}</option>
                    </select>
                </div>

                <div v-if="showMatch">
                    <label class="block text-sm font-medium text-gray-700">Match</label>
                    <input v-model="form.match" type="text" class="mt-1 w-full rounded-md border-gray-300 text-sm"
                        :placeholder="form.type === 'command' ? '/start' : 'hello'" />
                </div>

                <div v-if="showAliases">
                    <label class="block text-sm font-medium text-gray-700">Алиасы</label>
                    <div class="mt-1 space-y-2">
                        <div v-for="(alias, index) in form.aliases" :key="index" class="flex gap-2">
                            <input v-model="form.aliases[index]" type="text"
                                class="w-full rounded-md border-gray-300 text-sm"
                                placeholder="Синоним фразы" />
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
                            @input="form.controller_name = $event.target.value.replace(/[^a-zA-Z0-9]/g, '')"
                            class="w-full rounded-md border-gray-300 text-sm font-mono"
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
                        <BlockList v-model="form.handler_schema.blocks" />
                    </div>
                </template>

                <p v-else class="text-sm text-gray-500 italic">Обработчик задаётся у дочерних маршрутов</p>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Middleware (через запятую)</label>
                    <input :value="form.middleware.join(', ')"
                        @input="form.middleware = $event.target.value.split(',').map(s => s.trim()).filter(Boolean)"
                        type="text" class="mt-1 w-full rounded-md border-gray-300 text-sm" placeholder="auth, throttle" />
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t">
                    <button type="button" @click="emit('close')"
                        class="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                        Отмена
                    </button>
                    <button type="submit" :disabled="form.processing"
                        class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500 disabled:opacity-50">
                        {{ isEditing ? 'Сохранить' : 'Создать' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import Modal from '@/Components/Modal.vue';
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
            ? !isFallback.value
            : props.hasFallback;

        return {
            ...item,
            disabled,
            label: disabled && props.hasFallback && !isFallback.value ? 'Fallback (уже есть)' : item.label,
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
    { value: 'new_chat_members',   label: 'new_chat_members — новый участник в группе' },
    { value: 'left_chat_member',   label: 'left_chat_member — участник покинул группу' },
    { value: 'new_chat_title',     label: 'new_chat_title — изменение названия группы' },
    { value: 'new_chat_photo',     label: 'new_chat_photo — изменение фото группы' },
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
    <Modal
        :show="true"
        :title="(isEditing ? 'Редактировать' : 'Новый') + (isNested ? ' вложенный' : '') + ' маршрут'"
        max-width="lg"
        @close="emit('close')"
    >
        <form id="route-form" @submit.prevent="submit" class="re-form">
            <div class="re-body">
                <div v-if="!isNested" class="re-field">
                    <label class="re-lbl">Тип маршрута</label>
                    <select v-model="form.type" class="re-sel">
                        <option v-for="rt in routeTypes" :key="rt.value" :value="rt.value" :disabled="rt.disabled">
                            {{ rt.label }}
                        </option>
                    </select>
                </div>

                <div v-if="showMatch" class="re-field">
                    <label class="re-lbl">Match</label>
                    <ErInput
                        v-model="form.match"
                        :placeholder="form.type === 'command' ? '/start' : 'hello'"
                        long
                    />
                </div>

                <div v-if="form.type === 'event'" class="re-field">
                    <label class="re-lbl">Событие</label>
                    <select v-model="form.match" class="re-sel">
                        <option v-for="et in EVENT_TYPES" :key="et.value" :value="et.value">{{ et.label }}</option>
                    </select>
                </div>

                <div v-if="form.type === 'command'" class="re-field">
                    <label class="re-lbl">Описание для меню Telegram</label>
                    <ErInput v-model="form.description" :long="true" placeholder="Запустить бота" />
                    <p class="re-hint">Если пусто — команда не попадёт в меню при <code>bot:profile-sync</code>.</p>
                    <p v-if="form.errors.description" class="re-err">{{ form.errors.description }}</p>
                </div>

                <div v-if="showAliases" class="re-field">
                    <label class="re-lbl">Алиасы</label>
                    <div class="re-aliases">
                        <div v-for="(alias, index) in form.aliases" :key="index" class="re-alias-row">
                            <ErInput v-model="form.aliases[index]" :long="true" placeholder="Синоним фразы" />
                            <button type="button" class="re-rm" @click="removeAlias(index)">✕</button>
                        </div>
                    </div>
                    <button type="button" class="er-btn sm" style="margin-top:4px" @click="addAlias">+ Добавить алиас</button>
                </div>

                <div v-if="showControllerName" class="re-field">
                    <label class="re-lbl">{{ isNested ? 'Имя метода' : 'Имя контроллера' }}</label>
                    <div class="re-mono-row">
                        <input
                            :value="form.controller_name"
                            type="text"
                            class="re-mono-inp"
                            :placeholder="autoControllerName || (isNested ? 'method' : 'Controller')"
                            @input="onControllerNameInput($event)"
                        />
                        <button v-if="form.controller_name" type="button" class="re-rm" @click="form.controller_name = ''">✕</button>
                    </div>
                    <p v-if="isNested" class="re-hint">camelCase. Например: <code>manicure</code></p>
                    <p v-else-if="isParentPhrase" class="re-hint">PascalCase, без суффикса Controller. Например: <code>Price</code> → <code>PriceController</code></p>
                    <p v-else class="re-hint">PascalCase, без суффикса Controller</p>
                    <p v-if="longMatch && !form.controller_name" class="re-warn">Фраза длинная — рекомендуется задать короткое имя вручную</p>
                    <p v-if="controllerNameWarning" class="re-warn">{{ controllerNameWarning }}</p>
                    <p v-if="form.errors.controller_name" class="re-err">{{ form.errors.controller_name }}</p>
                </div>

                <template v-if="showHandler">
                    <div class="re-field">
                        <label class="re-lbl">Обработчик</label>
                        <div class="re-toggle">
                            <button
                                type="button"
                                class="re-toggle-btn"
                                :class="{ act: form.handler_type === 'controller' }"
                                @click="form.handler_type = 'controller'"
                            >Controller</button>
                            <button
                                type="button"
                                class="re-toggle-btn"
                                :class="{ act: form.handler_type === 'flow' }"
                                @click="form.handler_type = 'flow'"
                            >Flow</button>
                        </div>
                    </div>

                    <div v-if="form.handler_type === 'flow'" class="re-field">
                        <label class="re-lbl">Flow-диалог</label>
                        <select v-model="form.flow_id" class="re-sel">
                            <option :value="null">-- Выберите --</option>
                            <option v-for="f in flows" :key="f.id" :value="f.id">{{ f.name }}</option>
                        </select>
                    </div>

                    <div v-if="form.handler_type === 'controller'" class="re-field">
                        <label class="re-lbl">Блоки</label>
                        <BlockList v-model="form.handler_schema.blocks" :bot-id="botId" />
                    </div>
                </template>

                <p v-else class="re-hint-it">Обработчик задаётся у дочерних маршрутов</p>

                <div class="re-field">
                    <label class="re-lbl">Middleware (через запятую)</label>
                    <ErInput
                        :value="form.middleware.join(', ')"
                        @update:modelValue="form.middleware = $event.split(',').map(s => s.trim()).filter(Boolean)"
                        :long="true"
                        placeholder="auth, throttle"
                    />
                </div>
            </div>

            <div class="re-foot">
                <ErButton type="button" @click="emit('close')">Отмена</ErButton>
                <ErButton variant="primary" type="submit" :disabled="form.processing">
                    {{ isEditing ? 'Сохранить' : 'Создать' }}
                </ErButton>
            </div>
        </form>
    </Modal>
</template>

<style scoped>
.re-form {
    display: flex;
    flex-direction: column;
    min-height: 0;
    overflow: hidden;
}
.re-body {
    flex: 1;
    overflow-y: auto;
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 14px;
    min-height: 0;
}
.re-foot {
    flex-shrink: 0;
    padding: 10px 14px;
    border-top: 1px solid var(--bdr);
    background: linear-gradient(180deg, #f4f6f8 0%, #e8ecf0 100%);
    display: flex;
    justify-content: flex-end;
    gap: 8px;
}

.re-field { display: flex; flex-direction: column; gap: 4px; }
.re-lbl { font-size: 11px; font-weight: 600; color: var(--ink-2); }

.re-sel {
    height: 26px;
    padding: 0 8px;
    border: 1px solid var(--bdr-d);
    border-radius: var(--r-sm);
    background: #fff;
    color: var(--ink);
    font-size: 12px;
    font-family: var(--font);
    box-shadow: inset 0 1px 1px rgba(0,0,0,.05);
    width: 100%;
    cursor: pointer;
}
.re-sel:focus {
    outline: none;
    border-color: var(--blue);
    box-shadow: 0 0 0 2px rgba(58,114,196,.2);
}

.re-mono-row { display: flex; gap: 6px; align-items: center; }
.re-mono-inp {
    flex: 1;
    height: 26px;
    padding: 0 8px;
    border: 1px solid var(--bdr-d);
    border-radius: var(--r-sm);
    background: #fff;
    color: var(--ink);
    font-size: 12px;
    font-family: var(--mono);
    box-shadow: inset 0 1px 1px rgba(0,0,0,.05);
}
.re-mono-inp:focus {
    outline: none;
    border-color: var(--blue);
    box-shadow: 0 0 0 2px rgba(58,114,196,.2);
}

.re-aliases { display: flex; flex-direction: column; gap: 6px; }
.re-alias-row { display: flex; gap: 6px; align-items: center; }
.re-rm {
    flex-shrink: 0;
    background: none;
    border: none;
    color: var(--ink-4);
    cursor: pointer;
    padding: 0 4px;
    font-size: 13px;
    line-height: 1;
}
.re-rm:hover { color: var(--red); }


.re-toggle {
    display: flex;
    border: 1px solid var(--bdr-d);
    border-radius: var(--r-sm);
    overflow: hidden;
    width: fit-content;
}
.re-toggle-btn {
    padding: 4px 16px;
    font-size: 12px;
    font-weight: 500;
    background: #fff;
    color: var(--ink-2);
    border: none;
    cursor: pointer;
    font-family: var(--font);
    transition: background .1s, color .1s;
}
.re-toggle-btn + .re-toggle-btn { border-left: 1px solid var(--bdr-d); }
.re-toggle-btn.act { background: var(--blue); color: #fff; }
.re-toggle-btn:not(.act):hover { background: var(--surface-2); }

.re-hint { font-size: 11px; color: var(--ink-3); }
.re-hint code { font-family: var(--mono); background: var(--surface-3); padding: 1px 3px; border-radius: 2px; }
.re-hint-it { font-size: 12px; color: var(--ink-3); font-style: italic; }
.re-warn { font-size: 11px; color: var(--orange); }
.re-err  { font-size: 11px; color: var(--red); }
</style>
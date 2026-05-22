<script setup>
import { ref, computed, watch } from 'vue'
import { useForm, Head, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import BlockList from '@/Components/Routes/BlockList.vue'
import ToggleGroup from '@/Components/Ui/ToggleGroup.vue'
import ErButton from '@/Components/Ui/ErButton.vue'
import ErInput from '@/Components/Ui/ErInput.vue'
import { toCamelCase, toPascalCase, sanitizeIdentifier, identifierWarning } from '@/utils/translit'

const props = defineProps({
    bot:         Object,
    botRoute:    Object,
    flows:       Array,
    hasChildren: Boolean,
    can:         Object,
})

const isNested       = computed(() => !!props.botRoute.parent_id)
const isParentPhrase = computed(() => props.hasChildren)
const showHandler    = computed(() => !isParentPhrase.value)
const isFallback     = computed(() => props.botRoute.type === 'fallback')

const EVENT_TYPES = [
    { value: 'new_chat_members',   label: 'new_chat_members — новый участник в группе' },
    { value: 'left_chat_member',   label: 'left_chat_member — участник покинул группу' },
    { value: 'new_chat_title',     label: 'new_chat_title — изменение названия группы' },
    { value: 'new_chat_photo',     label: 'new_chat_photo — изменение фото группы' },
    { value: 'group_chat_created', label: 'group_chat_created — создание группы' },
]

const routeTypes = computed(() => {
    const items = [
        { value: 'command',  label: 'Command' },
        { value: 'phrase',   label: 'Phrase' },
        { value: 'pattern',  label: 'Pattern' },
        { value: 'action',   label: 'Action' },
        { value: 'event',    label: 'Event' },
        { value: 'media',    label: 'Media' },
        { value: 'location', label: 'Location' },
        { value: 'contact',  label: 'Contact' },
        { value: 'referral', label: 'Referral' },
        { value: 'fallback', label: 'Fallback' },
    ]
    return items.map(item => ({
        ...item,
        disabled: item.value !== 'fallback' ? isFallback.value : !isFallback.value,
    }))
})

const form = useForm({
    type:            props.botRoute.type,
    match:           props.botRoute.match ?? '',
    description:     props.botRoute.description ?? '',
    aliases:         props.botRoute.aliases ?? [],
    controller_name: props.botRoute.controller_name ?? '',
    handler_type:    props.botRoute.handler_type ?? 'controller',
    flow_id:         props.botRoute.flow_id ?? null,
    handler_schema:  props.botRoute.handler_schema ?? { blocks: [] },
    middleware:      props.botRoute.middleware ?? [],
})

const showMatch          = computed(() => ['command', 'phrase', 'pattern', 'action', 'referral'].includes(form.type))
const showAliases        = computed(() => form.type === 'phrase')
const longMatch          = computed(() => (form.match?.length ?? 0) > 20)
const showControllerName = computed(() => {
    if (form.type === 'fallback') return false
    return isParentPhrase.value || isNested.value || (showHandler.value && form.handler_type === 'controller')
})
const autoControllerName = computed(() => {
    const match = form.match?.trim()
    if (!match) return ''
    return isNested.value ? toCamelCase(match) : toPascalCase(match)
})

const controllerNameWarning = ref('')
let controllerNameWarningTimer = null

function onControllerNameInput(e) {
    const raw   = e.target.value
    const clean = sanitizeIdentifier(raw)
    const warn  = identifierWarning(raw)
    if (warn) {
        controllerNameWarning.value = warn
        clearTimeout(controllerNameWarningTimer)
        controllerNameWarningTimer = setTimeout(() => { controllerNameWarning.value = '' }, 3000)
    } else {
        controllerNameWarning.value = ''
    }
    form.controller_name = clean
}

watch(() => form.type, (newType) => {
    if (newType !== 'phrase') form.aliases = []
    if (newType === 'fallback') form.controller_name = ''
    if (newType === 'event' && !EVENT_TYPES.some(e => e.value === form.match)) {
        form.match = EVENT_TYPES[0].value
    }
})

function submit() {
    form.put(route('bot-routes.update', [props.bot.id, props.botRoute.id]))
}

const backUrl = route('bots.edit', props.bot.id) + '?tab=routes'
</script>

<template>
    <Head :title="`Маршрут — ${bot.name}`" />
    <AuthenticatedLayout :title="bot.name">
        <template #subbar>
            <nav class="re-bcr">
                <Link :href="route('dashboard')">Главная</Link>
                <span class="sep">›</span>
                <Link :href="backUrl">{{ bot.name }}</Link>
                <span class="sep">›</span>
                <span>Маршрут: {{ botRoute.type }}{{ botRoute.match ? ' ' + botRoute.match : '' }}</span>
            </nav>
        </template>
        <template #actions>
            <ErButton as="a" :href="backUrl">Назад</ErButton>
            <ErButton v-if="can.update" variant="primary" :disabled="form.processing" @click="submit">Сохранить</ErButton>
        </template>

        <div class="re-page">
            <form @submit.prevent="submit">
                <div v-if="!isNested" class="re-field">
                    <label class="re-lbl">Тип маршрута</label>
                    <select v-model="form.type" class="re-sel">
                        <option v-for="rt in routeTypes" :key="rt.value" :value="rt.value" :disabled="rt.disabled">{{ rt.label }}</option>
                    </select>
                </div>

                <div v-if="showMatch" class="re-field">
                    <label class="re-lbl">Match</label>
                    <ErInput v-model="form.match" :placeholder="form.type === 'command' ? '/start' : 'hello'" long />
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
                            <button type="button" class="re-rm" @click="form.aliases.splice(index, 1)">✕</button>
                        </div>
                    </div>
                    <button type="button" class="er-btn sm" style="margin-top:4px" @click="form.aliases.push('')">+ Добавить алиас</button>
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
                    <p v-else-if="isParentPhrase" class="re-hint">PascalCase, без суффикса Controller. Например: <code>Price</code></p>
                    <p v-else class="re-hint">PascalCase, без суффикса Controller</p>
                    <p v-if="longMatch && !form.controller_name" class="re-warn">Фраза длинная — рекомендуется задать короткое имя вручную</p>
                    <p v-if="controllerNameWarning" class="re-warn">{{ controllerNameWarning }}</p>
                    <p v-if="form.errors.controller_name" class="re-err">{{ form.errors.controller_name }}</p>
                </div>

                <template v-if="showHandler">
                    <div class="re-field">
                        <label class="re-lbl">Обработчик</label>
                        <ToggleGroup
                            v-model="form.handler_type"
                            :options="[{ value: 'controller', label: 'Controller' }, { value: 'flow', label: 'Flow' }]"
                        />
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
                        <BlockList v-model="form.handler_schema.blocks" :bot-id="bot.id" />
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
            </form>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.re-bcr { display: flex; align-items: center; gap: 5px; font-size: 11px; color: var(--ink-3); flex-shrink: 0; }
.re-bcr a { color: var(--blue); text-decoration: none; }
.re-bcr a:hover { text-decoration: underline; }
.re-bcr .sep { color: var(--bdr-d); }

.re-page {
    max-width: 640px;
    display: flex;
    flex-direction: column;
    gap: 14px;
}
.re-page form { display: flex; flex-direction: column; gap: 14px; }

.re-field { display: flex; flex-direction: column; gap: 4px; }
.re-lbl { font-size: 11px; font-weight: 600; color: var(--ink-2); }

.re-sel {
    height: 26px; padding: 0 8px; border: 1px solid var(--bdr-d);
    border-radius: var(--r-sm); background: #fff; color: var(--ink);
    font-size: 12px; font-family: var(--font); width: 100%;
    box-shadow: inset 0 1px 1px rgba(0,0,0,.05); cursor: pointer;
}
.re-sel:focus { outline: none; border-color: var(--blue); box-shadow: 0 0 0 2px rgba(58,114,196,.2); }

.re-mono-row { display: flex; gap: 6px; align-items: center; }
.re-mono-inp {
    flex: 1; height: 26px; padding: 0 8px; border: 1px solid var(--bdr-d);
    border-radius: var(--r-sm); background: #fff; color: var(--ink);
    font-size: 12px; font-family: var(--mono);
    box-shadow: inset 0 1px 1px rgba(0,0,0,.05);
}
.re-mono-inp:focus { outline: none; border-color: var(--blue); box-shadow: 0 0 0 2px rgba(58,114,196,.2); }

.re-aliases { display: flex; flex-direction: column; gap: 6px; }
.re-alias-row { display: flex; gap: 6px; align-items: center; }
.re-rm { flex-shrink: 0; background: none; border: none; color: var(--ink-4); cursor: pointer; padding: 0 4px; font-size: 13px; line-height: 1; }
.re-rm:hover { color: var(--red); }

.re-hint { font-size: 11px; color: var(--ink-3); }
.re-hint code { font-family: var(--mono); background: var(--surface-3); padding: 1px 3px; border-radius: 2px; }
.re-hint-it { font-size: 12px; color: var(--ink-3); font-style: italic; }
.re-warn { font-size: 11px; color: var(--orange); }
.re-err  { font-size: 11px; color: var(--red); }
</style>
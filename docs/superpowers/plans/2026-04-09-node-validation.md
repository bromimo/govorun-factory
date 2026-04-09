# Валидация ввода на ask-нодах — план реализации

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Добавить рантайм-валидацию ввода пользователя бота на нодах `ask_text`/`ask_keyboard` с правилами в стиле Laravel, кастомными сообщениями ошибок и настройкой дефолтов на уровне бота.

**Architecture:** Правила валидации хранятся в `data.validation` ноды (массив объектов `{ name, params?, message? }`). Дефолтные сообщения — в `bot.config.validation_messages`. UI: collapsible секция в формах ask-нод + вкладка в настройках бота. На канвасе — иконка щита на нодах с валидацией.

**Tech Stack:** Vue 3, Tailwind CSS, Laravel 13, Inertia.js

**Spec:** `docs/superpowers/specs/2026-04-09-node-validation-design.md`

---

### Task 1: Константы и утилиты валидации (JS)

**Files:**
- Create: `resources/js/Components/Blocks/validationRules.js`

- [ ] **Step 1: Создать файл с определениями правил и дефолтными сообщениями**

```js
// resources/js/Components/Blocks/validationRules.js

export const validationRuleDefs = [
    { name: 'required', label: 'Обязательное', group: 'general', params: [] },
    { name: 'string', label: 'Текст', group: 'type', params: [] },
    { name: 'numeric', label: 'Число', group: 'type', params: [] },
    { name: 'integer', label: 'Целое число', group: 'type', params: [] },
    { name: 'date', label: 'Дата', group: 'type', params: [] },
    { name: 'email', label: 'Email', group: 'format', params: [] },
    { name: 'url', label: 'URL', group: 'format', params: [] },
    { name: 'phone', label: 'Телефон', group: 'format', params: [] },
    { name: 'regex', label: 'Регулярное выражение', group: 'format', params: [{ key: 'pattern', label: 'Паттерн', placeholder: '^[A-Za-z]+$' }] },
    { name: 'min', label: 'Минимум', group: 'range', params: [{ key: 'value', label: 'Значение', type: 'number' }] },
    { name: 'max', label: 'Максимум', group: 'range', params: [{ key: 'value', label: 'Значение', type: 'number' }] },
    { name: 'between', label: 'Диапазон', group: 'range', params: [{ key: 'min', label: 'Мин', type: 'number' }, { key: 'max', label: 'Макс', type: 'number' }] },
    { name: 'in', label: 'Одно из значений', group: 'range', params: [{ key: 'values', label: 'Значения (через запятую)', placeholder: 'да, нет, может быть' }] },
];

export const ruleGroups = [
    { key: 'general', label: 'Общие' },
    { key: 'type', label: 'Тип' },
    { key: 'format', label: 'Формат' },
    { key: 'range', label: 'Диапазон' },
];

export const defaultMessages = {
    required: 'Пожалуйста, введите значение',
    string: 'Значение должно быть текстом',
    numeric: 'Значение должно быть числом',
    integer: 'Значение должно быть целым числом',
    email: 'Введите корректный email',
    url: 'Введите корректный URL',
    phone: 'Введите корректный номер телефона',
    min: 'Минимальная длина: {0} символов',
    max: 'Максимальная длина: {0} символов',
    between: 'Значение должно быть от {0} до {1}',
    in: 'Допустимые значения: {0}',
    regex: 'Значение не соответствует формату',
    date: 'Введите корректную дату',
};

export const numericMessages = {
    min: 'Минимальное значение: {0}',
    max: 'Максимальное значение: {0}',
};

export function getDefaultMessage(ruleName, rules) {
    if ((ruleName === 'min' || ruleName === 'max') && rules.some(r => r.name === 'numeric' || r.name === 'integer')) {
        return numericMessages[ruleName] ?? defaultMessages[ruleName];
    }
    return defaultMessages[ruleName] ?? '';
}

export function formatMessage(template, params) {
    if (!params?.length) return template;
    return params.reduce((msg, val, i) => msg.replace(`{${i}}`, val), template);
}
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/Components/Blocks/validationRules.js
git commit -m "feat: добавить определения правил валидации и дефолтные сообщения"
```

---

### Task 2: Компонент ValidationEditor

**Files:**
- Create: `resources/js/Components/Blocks/ValidationEditor.vue`

- [ ] **Step 1: Создать компонент редактора правил валидации**

```vue
<!-- resources/js/Components/Blocks/ValidationEditor.vue -->
<script setup>
import { ref, computed } from 'vue';
import { validationRuleDefs, ruleGroups, defaultMessages, getDefaultMessage } from './validationRules.js';

const model = defineModel({ type: Array, default: () => [] });
const props = defineProps({
    botValidationMessages: { type: Object, default: () => ({}) },
});

const collapsed = ref(model.value.length === 0);
const dropdownOpen = ref(false);

const addedRuleNames = computed(() => new Set(model.value.map(r => r.name)));

const availableGroups = computed(() => {
    return ruleGroups
        .map(g => ({
            ...g,
            rules: validationRuleDefs.filter(r => r.group === g.key && !addedRuleNames.value.has(r.name)),
        }))
        .filter(g => g.rules.length > 0);
});

function addRule(ruleName) {
    const def = validationRuleDefs.find(r => r.name === ruleName);
    if (!def) return;
    const rule = { name: ruleName };
    if (def.params.length === 1) rule.params = [''];
    if (def.params.length === 2) rule.params = ['', ''];
    model.value = [...model.value, rule];
    dropdownOpen.value = false;
}

function removeRule(index) {
    model.value = model.value.filter((_, i) => i !== index);
}

function updateParam(index, paramIndex, value) {
    const updated = [...model.value];
    const params = [...(updated[index].params || [])];
    params[paramIndex] = value;
    updated[index] = { ...updated[index], params };
    model.value = updated;
}

function updateMessage(index, value) {
    const updated = [...model.value];
    updated[index] = { ...updated[index], message: value || undefined };
    model.value = updated;
}

function getRuleDef(name) {
    return validationRuleDefs.find(r => r.name === name);
}

function getPlaceholder(ruleName) {
    return props.botValidationMessages[ruleName] || defaultMessages[ruleName] || '';
}

function moveRule(index, direction) {
    const target = index + direction;
    if (target < 0 || target >= model.value.length) return;
    const updated = [...model.value];
    [updated[index], updated[target]] = [updated[target], updated[index]];
    model.value = updated;
}
</script>

<template>
    <div class="mt-3">
        <button type="button" @click="collapsed = !collapsed"
            class="flex w-full items-center gap-1 text-xs font-medium text-gray-500 hover:text-gray-700">
            <svg class="h-3 w-3 transition-transform" :class="{ 'rotate-90': !collapsed }" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
            </svg>
            Валидация
            <span v-if="model.length" class="ml-1 rounded-full bg-indigo-100 px-1.5 py-0.5 text-[10px] font-semibold text-indigo-700">
                {{ model.length }}
            </span>
        </button>

        <div v-if="!collapsed" class="mt-2 space-y-2">
            <div v-for="(rule, i) in model" :key="i"
                class="rounded border border-gray-200 bg-gray-50 p-2 space-y-1.5">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-1">
                        <button v-if="model.length > 1" type="button" @click="moveRule(i, -1)" :disabled="i === 0"
                            class="text-gray-400 hover:text-gray-600 disabled:opacity-30 text-xs">▲</button>
                        <button v-if="model.length > 1" type="button" @click="moveRule(i, 1)" :disabled="i === model.length - 1"
                            class="text-gray-400 hover:text-gray-600 disabled:opacity-30 text-xs">▼</button>
                        <span class="text-xs font-medium text-gray-700">{{ getRuleDef(rule.name)?.label ?? rule.name }}</span>
                    </div>
                    <button type="button" @click="removeRule(i)" class="text-red-400 hover:text-red-600 text-sm">✕</button>
                </div>

                <div v-if="getRuleDef(rule.name)?.params?.length" class="flex gap-2">
                    <div v-for="(param, pi) in getRuleDef(rule.name).params" :key="pi" class="flex-1">
                        <label class="block text-[10px] text-gray-400">{{ param.label }}</label>
                        <input :value="rule.params?.[pi] ?? ''" @input="updateParam(i, pi, $event.target.value)"
                            :type="param.type || 'text'" :placeholder="param.placeholder"
                            class="mt-0.5 w-full rounded border-gray-300 text-xs placeholder-gray-400" />
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] text-gray-400">Сообщение об ошибке</label>
                    <input :value="rule.message ?? ''" @input="updateMessage(i, $event.target.value)"
                        :placeholder="getPlaceholder(rule.name)"
                        class="mt-0.5 w-full rounded border-gray-300 text-xs placeholder-gray-400" />
                </div>
            </div>

            <div class="relative">
                <button type="button" @click="dropdownOpen = !dropdownOpen"
                    :disabled="addedRuleNames.size >= validationRuleDefs.length"
                    class="text-xs text-indigo-600 hover:text-indigo-800 disabled:text-gray-400 disabled:cursor-not-allowed">
                    + Добавить правило
                </button>

                <div v-if="dropdownOpen"
                    class="absolute left-0 top-6 z-10 w-56 rounded-md border border-gray-200 bg-white py-1 shadow-lg">
                    <div v-for="group in availableGroups" :key="group.key">
                        <div class="px-3 py-1 text-[10px] font-semibold uppercase tracking-wide text-gray-400">{{ group.label }}</div>
                        <button v-for="r in group.rules" :key="r.name" type="button" @click="addRule(r.name)"
                            class="block w-full px-3 py-1 text-left text-xs text-gray-700 hover:bg-indigo-50 hover:text-indigo-700">
                            {{ r.label }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/Components/Blocks/ValidationEditor.vue
git commit -m "feat: компонент ValidationEditor для редактирования правил валидации"
```

---

### Task 3: Интеграция ValidationEditor в формы ask-нод

**Files:**
- Modify: `resources/js/Components/Blocks/AskTextForm.vue`
- Modify: `resources/js/Components/Blocks/AskKeyboardForm.vue`
- Modify: `resources/js/Components/Blocks/blockTypes.js:27-28` (defaultBlockParams)

- [ ] **Step 1: Обновить defaultBlockParams — добавить validation: []**

В `resources/js/Components/Blocks/blockTypes.js` изменить:

```js
// было
    ask_text: { text: '' },
    ask_keyboard: { text: '', buttons: [] },
```

```js
// стало
    ask_text: { text: '', validation: [] },
    ask_keyboard: { text: '', buttons: [], validation: [] },
```

- [ ] **Step 2: Добавить ValidationEditor в AskTextForm.vue**

Заменить весь файл `resources/js/Components/Blocks/AskTextForm.vue`:

```vue
<script setup>
import VarsHint from './VarsHint.vue';
import StateWarning from './StateWarning.vue';
import ValidationEditor from './ValidationEditor.vue';
import { useStateWarnings } from './useStateWarnings.js';

const model = defineModel({ type: Object, default: () => ({ text: '', validation: [] }) });
const props = defineProps({
    allStateKeys: { type: Array, default: () => [] },
    declaredStateKeys: { type: Array, default: () => [] },
    botValidationMessages: { type: Object, default: () => ({}) },
});

const { uninitializedKeys, undeclaredKeys } = useStateWarnings(
    () => model.value.text, () => props.allStateKeys, () => props.declaredStateKeys,
);
</script>

<template>
    <div>
        <label class="block text-xs font-medium text-gray-500">Текст вопроса</label>
        <textarea v-model="model.text" rows="3" class="mt-1 w-full rounded border-gray-300 text-sm placeholder-gray-400" placeholder="Как вас зовут?" />
        <StateWarning :uninitialized-keys="uninitializedKeys" :undeclared-keys="undeclaredKeys" />
        <VarsHint />
        <ValidationEditor v-model="model.validation" :bot-validation-messages="botValidationMessages" />
    </div>
</template>
```

- [ ] **Step 3: Добавить ValidationEditor в AskKeyboardForm.vue**

Заменить весь файл `resources/js/Components/Blocks/AskKeyboardForm.vue`:

```vue
<script setup>
import VarsHint from './VarsHint.vue';
import StateWarning from './StateWarning.vue';
import ButtonEditor from './ButtonEditor.vue';
import ValidationEditor from './ValidationEditor.vue';
import { useStateWarnings } from './useStateWarnings.js';

const model = defineModel({ type: Object, default: () => ({ text: '', buttons: [], validation: [] }) });
const props = defineProps({
    allStateKeys: { type: Array, default: () => [] },
    declaredStateKeys: { type: Array, default: () => [] },
    botValidationMessages: { type: Object, default: () => ({}) },
});

const { uninitializedKeys, undeclaredKeys } = useStateWarnings(
    () => model.value.text, () => props.allStateKeys, () => props.declaredStateKeys,
);
</script>

<template>
    <div class="space-y-3">
        <div>
            <label class="block text-xs font-medium text-gray-500">Текст вопроса</label>
            <textarea v-model="model.text" rows="2" class="mt-1 w-full rounded border-gray-300 text-sm placeholder-gray-400" />
            <StateWarning :uninitialized-keys="uninitializedKeys" :undeclared-keys="undeclaredKeys" />
            <VarsHint />
        </div>
        <ButtonEditor v-model="model.buttons" />
        <ValidationEditor v-model="model.validation" :bot-validation-messages="botValidationMessages" />
    </div>
</template>
```

- [ ] **Step 4: Прокинуть botValidationMessages через BlockFormResolver**

В `resources/js/Components/Blocks/BlockFormResolver.vue` добавить проп и передать:

```vue
<script setup>
import { computed } from 'vue';
import ReplyTextForm from './ReplyTextForm.vue';
import ReplyKeyboardForm from './ReplyKeyboardForm.vue';
import SaveStateForm from './SaveStateForm.vue';
import AskTextForm from './AskTextForm.vue';
import AskKeyboardForm from './AskKeyboardForm.vue';
import ConditionForm from './ConditionForm.vue';
import ReplyMediaForm from './ReplyMediaForm.vue';
import ApiCallForm from './ApiCallForm.vue';

const model = defineModel({ type: Object });
const props = defineProps({
    type: String,
    allStateKeys: { type: Array, default: () => [] },
    declaredStateKeys: { type: Array, default: () => [] },
    botValidationMessages: { type: Object, default: () => ({}) },
});

const formComponent = computed(() => ({
    reply_text: ReplyTextForm,
    reply_keyboard: ReplyKeyboardForm,
    reply_media: ReplyMediaForm,
    save_state: SaveStateForm,
    ask_text: AskTextForm,
    ask_keyboard: AskKeyboardForm,
    condition: ConditionForm,
    api_call: ApiCallForm,
})[props.type] ?? null);
</script>

<template>
    <component v-if="formComponent" :is="formComponent" v-model="model"
        :all-state-keys="allStateKeys" :declared-state-keys="declaredStateKeys"
        :bot-validation-messages="botValidationMessages" />
    <p v-else class="text-xs text-gray-400">Нет параметров для этого типа</p>
</template>
```

- [ ] **Step 5: Commit**

```bash
git add resources/js/Components/Blocks/blockTypes.js resources/js/Components/Blocks/AskTextForm.vue resources/js/Components/Blocks/AskKeyboardForm.vue resources/js/Components/Blocks/BlockFormResolver.vue
git commit -m "feat: интеграция ValidationEditor в формы ask-нод"
```

---

### Task 4: Прокинуть botValidationMessages из страницы Flow-редактора

**Files:**
- Modify: `resources/js/Pages/Flows/Edit.vue`
- Modify: `resources/js/Components/Flows/NodeProperties.vue`
- Modify: `app/Http/Controllers/BotFlowController.php` (добавить bot.config в props)

- [ ] **Step 1: Убедиться что контроллер передаёт bot с config**

Прочитать `app/Http/Controllers/BotFlowController.php` и проверить что `bot` передаётся в `Flows/Edit` — поле `config` уже включено в модель Bot (оно в `$fillable` и кастуется к `array`), значит оно уже приходит на фронт. Если нет — добавить.

- [ ] **Step 2: В `resources/js/Pages/Flows/Edit.vue` вычислить botValidationMessages и передать в NodeProperties**

Добавить computed:

```js
const botValidationMessages = computed(() => props.bot.config?.validation_messages ?? {});
```

И прокинуть в `<NodeProperties>`:

```vue
<NodeProperties
    :node="selectedNode"
    :can-update="can.update"
    :all-node-ids="allNodeIds"
    :all-state-keys="allStateKeys"
    :declared-state-keys="declaredStateKeys"
    :bot-validation-messages="botValidationMessages"
    @update="onNodeDataUpdated"
    @rename="onNodeRenamed"
    @close="selectedNode = null"
/>
```

- [ ] **Step 3: В NodeProperties.vue принять и передать проп**

Добавить в props:

```js
botValidationMessages: { type: Object, default: () => ({}) },
```

И передать в `<BlockFormResolver>`:

```vue
<BlockFormResolver v-model="localData" :type="node.type"
    :all-state-keys="allStateKeys" :declared-state-keys="declaredStateKeys"
    :bot-validation-messages="botValidationMessages" />
```

- [ ] **Step 4: Commit**

```bash
git add resources/js/Pages/Flows/Edit.vue resources/js/Components/Flows/NodeProperties.vue
git commit -m "feat: прокинуть botValidationMessages до форм ask-нод"
```

---

### Task 5: Иконка щита на канвасе

**Files:**
- Modify: `resources/js/Components/Flows/nodes/BaseNode.vue`
- Modify: `resources/js/Components/Flows/nodes/AskTextNode.vue`
- Modify: `resources/js/Components/Flows/nodes/AskKeyboardNode.vue`

- [ ] **Step 1: Добавить проп hasValidation в BaseNode.vue и отобразить иконку**

Заменить весь файл `resources/js/Components/Flows/nodes/BaseNode.vue`:

```vue
<script setup>
import { Handle, Position } from '@vue-flow/core';
import { colorClasses } from '../../Blocks/blockTypes.js';

const props = defineProps({
    id: String,
    label: String,
    color: { type: String, default: 'gray' },
    hasInput: { type: Boolean, default: true },
    hasOutput: { type: Boolean, default: true },
    hasValidation: { type: Boolean, default: false },
    selected: Boolean,
});

const colors = colorClasses[props.color] ?? colorClasses.gray;
</script>

<template>
    <div class="relative rounded-lg border-2 shadow-sm min-w-[160px] max-w-[220px]"
        :class="[colors.border, colors.bg, selected ? 'ring-2 ring-indigo-400' : '']">
        <Handle v-if="hasInput" type="target" :position="Position.Top" :connectable-start="false" />

        <div class="px-3 py-1.5 text-xs font-bold border-b" :class="[colors.text, colors.border]">
            {{ label }}
        </div>

        <div class="px-3 py-2 text-xs text-gray-700">
            <slot />
        </div>

        <div v-if="hasValidation" class="absolute -top-1.5 -right-1.5 rounded-full bg-white p-0.5 shadow-sm border border-gray-200" title="Валидация настроена">
            <svg class="h-3 w-3 text-blue-500" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M9.661 2.237a.531.531 0 01.678 0 11.947 11.947 0 007.078 2.749.5.5 0 01.479.425c.069.52.104 1.05.104 1.59 0 5.162-3.26 9.563-7.834 11.256a.48.48 0 01-.332 0C5.26 16.564 2 12.163 2 7c0-.54.035-1.07.104-1.59a.5.5 0 01.48-.425 11.947 11.947 0 007.077-2.75zm4.196 5.954a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
            </svg>
        </div>

        <Handle v-if="hasOutput" type="source" :position="Position.Bottom" />
    </div>
</template>
```

SVG — Heroicons shield-check (mini, 20x20). Используется в коде выше.

- [ ] **Step 2: Передать hasValidation в AskTextNode.vue**

Заменить `resources/js/Components/Flows/nodes/AskTextNode.vue`:

```vue
<script setup>
import BaseNode from './BaseNode.vue';
defineOptions({ inheritAttrs: false });
defineProps(['id', 'data', 'selected']);
</script>
<template>
    <BaseNode :id="id" :selected="selected" label="Вопрос текстом" color="blue"
        :has-validation="data.validation?.length > 0">
        <p v-if="data.text" class="truncate">{{ data.text }}</p>
    </BaseNode>
</template>
```

- [ ] **Step 3: Передать hasValidation в AskKeyboardNode.vue**

Заменить `resources/js/Components/Flows/nodes/AskKeyboardNode.vue`:

```vue
<script setup>
import BaseNode from './BaseNode.vue';
defineOptions({ inheritAttrs: false });
defineProps(['id', 'data', 'selected']);
</script>
<template>
    <BaseNode :id="id" :selected="selected" label="Вопрос с клавиатурой" color="blue"
        :has-validation="data.validation?.length > 0">
        <p v-if="data.text" class="truncate">{{ data.text }}</p>
        <p v-if="data.buttons?.length" class="mt-1 text-gray-400">{{ data.buttons.length }} кнопок</p>
    </BaseNode>
</template>
```

- [ ] **Step 4: Commit**

```bash
git add resources/js/Components/Flows/nodes/BaseNode.vue resources/js/Components/Flows/nodes/AskTextNode.vue resources/js/Components/Flows/nodes/AskKeyboardNode.vue
git commit -m "feat: иконка щита на ask-нодах с валидацией"
```

---

### Task 6: Страница настроек дефолтных сообщений бота

**Files:**
- Create: `resources/js/Components/Bots/ValidationMessagesForm.vue`
- Modify: `resources/js/Pages/Bots/Edit.vue` (добавить вкладку)

- [ ] **Step 1: Создать компонент формы дефолтных сообщений**

```vue
<!-- resources/js/Components/Bots/ValidationMessagesForm.vue -->
<script setup>
import { useForm } from '@inertiajs/vue3';
import { validationRuleDefs, defaultMessages } from '../Blocks/validationRules.js';

const props = defineProps({
    bot: Object,
    can: Object,
});

const existing = props.bot.config?.validation_messages ?? {};

const form = useForm({
    config: {
        ...props.bot.config,
        validation_messages: Object.fromEntries(
            validationRuleDefs.map(r => [r.name, existing[r.name] ?? ''])
        ),
    },
});

function save() {
    const messages = { ...form.config.validation_messages };
    Object.keys(messages).forEach(k => { if (!messages[k]) delete messages[k]; });

    form.transform(data => ({
        config: { ...data.config, validation_messages: Object.keys(messages).length ? messages : undefined },
    })).put(route('bots.update', props.bot.id));
}

function hasParams(ruleName) {
    return validationRuleDefs.find(r => r.name === ruleName)?.params?.length > 0;
}
</script>

<template>
    <form @submit.prevent="save" class="space-y-4">
        <p class="text-sm text-gray-500">
            Настройте тексты сообщений об ошибках валидации для этого бота.
            Пустые поля используют значения по умолчанию.
        </p>

        <div v-for="rule in validationRuleDefs" :key="rule.name" class="space-y-1">
            <label class="block text-sm font-medium text-gray-700">{{ rule.label }}</label>
            <input v-model="form.config.validation_messages[rule.name]" type="text"
                :placeholder="defaultMessages[rule.name]"
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
            <p v-if="hasParams(rule.name)" class="text-xs text-gray-400">
                Используйте {0}, {1} для подстановки параметров
            </p>
        </div>

        <div v-if="can.update" class="pt-4">
            <button type="submit" :disabled="form.processing"
                class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500 disabled:opacity-50">
                Сохранить
            </button>
            <span v-if="form.recentlySuccessful" class="ml-3 text-sm text-green-600">Сохранено</span>
        </div>
    </form>
</template>
```

- [ ] **Step 2: Добавить вкладку "Валидация" в Bots/Edit.vue**

В `resources/js/Pages/Bots/Edit.vue`:

Добавить импорт:

```js
import ValidationMessagesForm from '@/Components/Bots/ValidationMessagesForm.vue';
```

Добавить вкладку в массив `tabs`:

```js
const tabs = [
    { key: 'settings', label: 'Настройки' },
    { key: 'routes', label: 'Маршруты' },
    { key: 'flows', label: 'Flow-диалоги' },
    { key: 'validation', label: 'Валидация' },
    { key: 'messengers', label: 'Мессенджеры' },
];
```

Добавить рендер вкладки в template (после FlowList и перед MessengerConfigForm):

```vue
<ValidationMessagesForm v-else-if="activeTab === 'validation'" :bot="bot" :can="can" />
```

- [ ] **Step 3: Добавить валидацию config.validation_messages в UpdateBotRequest.php**

В `app/Http/Requests/UpdateBotRequest.php` добавить правила:

```php
'config.validation_messages' => ['nullable', 'array'],
'config.validation_messages.*' => ['nullable', 'string', 'max:500'],
```

- [ ] **Step 4: Commit**

```bash
git add resources/js/Components/Bots/ValidationMessagesForm.vue resources/js/Pages/Bots/Edit.vue app/Http/Requests/UpdateBotRequest.php
git commit -m "feat: вкладка настройки дефолтных сообщений валидации бота"
```

---

### Task 7: Сохранение validation в graph JSON

**Files:**
- Modify: `resources/js/Components/Flows/FlowCanvas.vue:81-96` (getGraph)

- [ ] **Step 1: Обновить getGraph чтобы сохранять validation в data нод**

В `resources/js/Components/Flows/FlowCanvas.vue` метод `getGraph`, секция маппинга нод. Сейчас:

```js
nodes: obj.nodes.map(n => ({
    id: n.id,
    type: n.type,
    data: n.data,
    position: n.position,
})),
```

`data: n.data` уже включает все поля data, включая `validation`. Значит **getGraph уже сохраняет validation** — никаких изменений не нужно.

Проверить что `validation: []` (пустой массив) не загромождает JSON. Обновить маппинг нод:

```js
nodes: obj.nodes.map(n => {
    const data = { ...n.data };
    if (Array.isArray(data.validation) && data.validation.length === 0) {
        delete data.validation;
    }
    return { id: n.id, type: n.type, data, position: n.position };
}),
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/Components/Flows/FlowCanvas.vue
git commit -m "feat: не сохранять пустой массив validation в graph JSON"
```

---

### Task 8: Тест — правила валидации в graph JSON

**Files:**
- Create: `tests/Feature/BotFlowValidationTest.php`

- [ ] **Step 1: Написать тест что graph с validation сохраняется и читается**

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Bot;
use App\Models\User;
use App\Models\BotFlow;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;

/** Тесты сохранения правил валидации в graph flow. */
class BotFlowValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_flow_graph_stores_validation_rules(): void
    {
        $user = User::factory()->create(['role' => UserRole::Admin->value]);
        $bot = Bot::factory()->create(['created_by' => $user->id]);
        $flow = BotFlow::factory()->create(['bot_id' => $bot->id]);

        $graph = [
            'nodes' => [
                [
                    'id' => 'ask_text_1',
                    'type' => 'ask_text',
                    'data' => [
                        'text' => 'Введите email',
                        'validation' => [
                            ['name' => 'required', 'message' => 'Обязательное поле'],
                            ['name' => 'email'],
                            ['name' => 'max', 'params' => [255]],
                        ],
                    ],
                    'position' => ['x' => 0, 'y' => 0],
                ],
            ],
            'edges' => [],
        ];

        $this->actingAs($user)
            ->put(route('bot-flows.update', [$bot, $flow]), [
                'name' => $flow->name,
                'graph' => $graph,
            ])
            ->assertRedirect();

        $flow->refresh();
        $node = $flow->graph['nodes'][0];

        $this->assertEquals('ask_text_1', $node['id']);
        $this->assertCount(3, $node['data']['validation']);
        $this->assertEquals('required', $node['data']['validation'][0]['name']);
        $this->assertEquals('Обязательное поле', $node['data']['validation'][0]['message']);
        $this->assertEquals([255], $node['data']['validation'][2]['params']);
    }

    public function test_bot_config_stores_validation_messages(): void
    {
        $user = User::factory()->create(['role' => UserRole::Admin->value]);
        $bot = Bot::factory()->create(['created_by' => $user->id]);

        $this->actingAs($user)
            ->put(route('bots.update', $bot), [
                'config' => [
                    'validation_messages' => [
                        'required' => 'Заполните поле',
                        'email' => 'Некорректный email',
                    ],
                ],
            ])
            ->assertRedirect();

        $bot->refresh();
        $this->assertEquals('Заполните поле', $bot->config['validation_messages']['required']);
        $this->assertEquals('Некорректный email', $bot->config['validation_messages']['email']);
    }
}
```

- [ ] **Step 2: Запустить тест**

```bash
php artisan test tests/Feature/BotFlowValidationTest.php
```

Expected: 2 tests PASS.

- [ ] **Step 3: Commit**

```bash
git add tests/Feature/BotFlowValidationTest.php
git commit -m "test: тесты сохранения правил валидации в graph и config бота"
```

---

### Task 9: Закрытие dropdown при клике снаружи

**Files:**
- Modify: `resources/js/Components/Blocks/ValidationEditor.vue`

- [ ] **Step 1: Добавить закрытие dropdown при клике вне его**

В `resources/js/Components/Blocks/ValidationEditor.vue` добавить в `<script setup>`:

```js
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';

const dropdownRef = ref(null);

function handleClickOutside(e) {
    if (dropdownRef.value && !dropdownRef.value.contains(e.target)) {
        dropdownOpen.value = false;
    }
}

onMounted(() => document.addEventListener('click', handleClickOutside));
onBeforeUnmount(() => document.removeEventListener('click', handleClickOutside));
```

Обернуть кнопку и dropdown в `<div ref="dropdownRef">`:

```vue
<div ref="dropdownRef" class="relative">
    <button type="button" @click="dropdownOpen = !dropdownOpen" ...>
        + Добавить правило
    </button>
    <div v-if="dropdownOpen" class="absolute ...">
        ...
    </div>
</div>
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/Components/Blocks/ValidationEditor.vue
git commit -m "fix: закрытие dropdown правил валидации при клике снаружи"
```

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { validationRuleDefs, ruleGroups, getDefaultMessage } from './validationRules.js';

const model = defineModel({ type: Array, default: () => [] });
const props = defineProps({
    botValidationMessages: { type: Object, default: () => ({}) },
});

const collapsed = ref(model.value.length === 0);
const dropdownOpen = ref(false);
const dropdownRef = ref(null);

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
    return props.botValidationMessages[ruleName] || getDefaultMessage(ruleName, model.value) || '';
}

function moveRule(index, direction) {
    const target = index + direction;
    if (target < 0 || target >= model.value.length) return;
    const updated = [...model.value];
    [updated[index], updated[target]] = [updated[target], updated[index]];
    model.value = updated;
}

function handleClickOutside(e) {
    if (dropdownRef.value && !dropdownRef.value.contains(e.target)) {
        dropdownOpen.value = false;
    }
}

onMounted(() => document.addEventListener('click', handleClickOutside));
onBeforeUnmount(() => document.removeEventListener('click', handleClickOutside));
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
            <div v-for="(rule, i) in model" :key="rule.name"
                class="rounded border border-gray-200 bg-gray-50 p-2 space-y-1.5">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-1">
                        <button v-if="model.length > 1" type="button" @click="moveRule(i, -1)" :disabled="i === 0"
                            class="text-gray-400 hover:text-gray-600 disabled:opacity-30 text-xs">&#9650;</button>
                        <button v-if="model.length > 1" type="button" @click="moveRule(i, 1)" :disabled="i === model.length - 1"
                            class="text-gray-400 hover:text-gray-600 disabled:opacity-30 text-xs">&#9660;</button>
                        <span class="text-xs font-medium text-gray-700">{{ getRuleDef(rule.name)?.label ?? rule.name }}</span>
                    </div>
                    <button type="button" @click="removeRule(i)" class="text-red-400 hover:text-red-600 text-sm">&times;</button>
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

            <div ref="dropdownRef" class="relative">
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

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { validationRuleDefs, ruleGroups, getDefaultMessage } from './validationRules.js';

const model = defineModel({ type: Array, default: () => [] });
const props = defineProps({
    botValidationMessages: { type: Object, default: () => ({}) },
    allowedRules: { type: Array, default: null },
});

const collapsed = ref(model.value.length === 0);
const dropdownOpen = ref(false);
const dropdownRef = ref(null);

const addedRuleNames = computed(() => new Set(model.value.map(r => r.name)));

const allowedDefs = computed(() =>
    props.allowedRules ? validationRuleDefs.filter(r => props.allowedRules.includes(r.name)) : validationRuleDefs,
);

const availableGroups = computed(() => {
    return ruleGroups
        .map(g => ({
            ...g,
            rules: allowedDefs.value.filter(r => r.group === g.key && !addedRuleNames.value.has(r.name)),
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
    <div class="ve-wrap">
        <button type="button" @click="collapsed = !collapsed" class="ve-toggle">
            <svg class="ve-arrow" :class="{ 'is-open': !collapsed }" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
            </svg>
            Валидация
            <span v-if="model.length" class="ve-count">{{ model.length }}</span>
        </button>

        <div v-if="!collapsed" class="ve-body">
            <div v-for="(rule, i) in model" :key="rule.name" class="ve-rule">
                <div class="ve-rule-header">
                    <div class="ve-rule-order">
                        <button v-if="model.length > 1" type="button" @click="moveRule(i, -1)" :disabled="i === 0"
                            class="ve-order-btn">&#9650;</button>
                        <button v-if="model.length > 1" type="button" @click="moveRule(i, 1)" :disabled="i === model.length - 1"
                            class="ve-order-btn">&#9660;</button>
                        <span class="ve-rule-name">{{ getRuleDef(rule.name)?.label ?? rule.name }}</span>
                    </div>
                    <button type="button" @click="removeRule(i)" class="ve-remove">&times;</button>
                </div>

                <div v-if="getRuleDef(rule.name)?.params?.length" class="ve-params">
                    <div v-for="(param, pi) in getRuleDef(rule.name).params" :key="pi" class="ve-param">
                        <label class="ve-param-lbl">{{ param.label }}</label>
                        <input :value="rule.params?.[pi] ?? ''" @input="updateParam(i, pi, $event.target.value)"
                            :type="param.type || 'text'" :placeholder="param.placeholder"
                            class="field-input mt-1" />
                    </div>
                </div>

                <div>
                    <label class="ve-param-lbl">Сообщение об ошибке</label>
                    <input :value="rule.message ?? ''" @input="updateMessage(i, $event.target.value)"
                        :placeholder="getPlaceholder(rule.name)"
                        class="field-input mt-1" />
                </div>
            </div>

            <div ref="dropdownRef" class="ve-dd-wrap">
                <button type="button" @click="dropdownOpen = !dropdownOpen"
                    :disabled="addedRuleNames.size >= allowedDefs.length"
                    class="er-btn sm">
                    + Добавить правило
                </button>

                <div v-if="dropdownOpen" class="ve-dropdown">
                    <div v-for="group in availableGroups" :key="group.key">
                        <div class="ve-dd-group">{{ group.label }}</div>
                        <button v-for="r in group.rules" :key="r.name" type="button" @click="addRule(r.name)"
                            class="ve-dd-item">
                            {{ r.label }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.ve-wrap { margin-top: 8px; }
.ve-toggle {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 11px;
    font-weight: 600;
    color: var(--ink-3);
    background: none;
    border: none;
    cursor: pointer;
    padding: 0;
    font-family: var(--font);
    text-transform: uppercase;
    letter-spacing: .04em;
}
.ve-toggle:hover { color: var(--ink-2); }
.ve-arrow { width: 12px; height: 12px; transition: transform .15s; flex-shrink: 0; }
.ve-arrow.is-open { transform: rotate(90deg); }
.ve-count {
    margin-left: 2px;
    border-radius: 20px;
    background: var(--blue-soft);
    padding: 0 5px 1px;
    font-size: 10px;
    font-weight: 700;
    color: var(--blue);
}
.ve-body { margin-top: 8px; display: flex; flex-direction: column; gap: 6px; }
.ve-rule {
    border-radius: var(--r-sm);
    border: 1px solid var(--bdr);
    background: var(--surface-2);
    padding: 8px;
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.ve-rule-header { display: flex; align-items: center; justify-content: space-between; }
.ve-rule-order { display: flex; align-items: center; gap: 4px; }
.ve-order-btn {
    font-size: 10px;
    color: var(--ink-4);
    background: none;
    border: none;
    cursor: pointer;
    padding: 0 2px;
    line-height: 1;
}
.ve-order-btn:hover { color: var(--ink-2); }
.ve-order-btn:disabled { opacity: .3; cursor: default; }
.ve-rule-name { font-size: 12px; font-weight: 600; color: var(--ink-2); }
.ve-remove { font-size: 14px; color: var(--red); background: none; border: none; cursor: pointer; padding: 0 2px; line-height: 1; }
.ve-remove:hover { opacity: .7; }
.ve-params { display: flex; gap: 8px; }
.ve-param { flex: 1; }
.ve-param-lbl { display: block; font-size: 10px; color: var(--ink-4); }
.ve-dd-wrap { position: relative; }
.ve-dd-wrap .er-btn { margin-top: 2px; }
.ve-dropdown {
    position: absolute;
    left: 0;
    top: calc(100% + 4px);
    z-index: 10;
    width: 200px;
    border-radius: var(--r-sm);
    border: 1px solid var(--bdr);
    background: #fff;
    padding: 4px 0;
    box-shadow: var(--sh-md);
}
.ve-dd-group {
    padding: 4px 10px 2px;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: var(--ink-4);
}
.ve-dd-item {
    display: block;
    width: 100%;
    padding: 4px 10px;
    text-align: left;
    font-size: 12px;
    color: var(--ink-2);
    background: none;
    border: none;
    cursor: pointer;
    font-family: var(--font);
}
.ve-dd-item:hover { background: var(--blue-soft); color: var(--blue); }
</style>
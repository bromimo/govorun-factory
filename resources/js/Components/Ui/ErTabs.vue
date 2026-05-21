<script setup>
defineOptions({ inheritAttrs: false });

const modelValue = defineModel({ type: String, required: true });

defineProps({
    tabs: { type: Array, required: true }, // [{ value, label, disabled? }]
});
</script>

<template>
    <div class="er-tabs" role="tablist" v-bind="$attrs">
        <button
            v-for="tab in tabs"
            :key="tab.value"
            type="button"
            role="tab"
            :aria-selected="modelValue === tab.value"
            :disabled="tab.disabled"
            class="er-tab"
            :class="{ act: modelValue === tab.value }"
            @click="!tab.disabled && (modelValue = tab.value)"
        >
            {{ tab.label }}
        </button>
    </div>
</template>

<style scoped>
.er-tabs {
    display: flex;
    border-bottom: 1px solid var(--bdr);
    background: var(--surface-2);
    padding: 0 12px;
}
.er-tab {
    padding: 0 14px;
    height: 32px;
    display: inline-flex;
    align-items: center;
    color: var(--ink-2);
    cursor: pointer;
    font-weight: 500;
    border-bottom: 2px solid transparent;
    margin-bottom: -1px;
    font-size: 12px;
    background: none;
    border-top: none;
    border-left: none;
    border-right: none;
    font-family: var(--font);
    transition: color .1s;
}
.er-tab:hover:not(:disabled) { color: var(--ink); }
.er-tab.act {
    color: var(--blue-d);
    border-bottom-color: var(--blue);
    font-weight: 600;
    background: linear-gradient(180deg, rgba(58,114,196,.04) 0%, transparent 100%);
}
.er-tab:disabled { color: var(--ink-4); cursor: not-allowed; }
</style>
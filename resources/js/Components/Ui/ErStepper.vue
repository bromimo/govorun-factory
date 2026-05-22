<script setup>
defineOptions({ inheritAttrs: false });

defineProps({
    n: { type: Number, required: true },
    total: { type: Number, default: null },
    title: { type: String, required: true },
    state: { type: String, default: 'default' },
});
</script>

<template>
    <h4 class="er-step-h" :class="state" v-bind="$attrs">
        <span class="er-step-num">
            {{ n }}<span v-if="total" class="er-step-total">/{{ total }}</span>
        </span>
        <span class="er-step-title">{{ title }}</span>
    </h4>
</template>

<style scoped>
.er-step-h {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0 0 8px;
    font-size: 12px;
    font-weight: 600;
    color: var(--ink);
    text-transform: none;
    letter-spacing: 0;
}
.er-step-num {
    flex: 0 0 auto;
    min-width: 22px;
    height: 22px;
    padding: 0 6px;
    border-radius: 9999px;
    background: var(--surface-2);
    border: 1px solid var(--bdr);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-family: var(--mono);
    color: var(--ink-2);
    font-weight: 600;
}
.er-step-h.active .er-step-num {
    background: var(--blue-soft);
    border-color: var(--blue);
    color: var(--blue-d);
}
.er-step-h.done .er-step-num {
    background: var(--green-soft);
    border-color: var(--green);
    color: var(--green);
}
.er-step-total {
    color: var(--ink-4);
    margin-left: 1px;
}
.er-step-title {
    color: var(--ink);
}
</style>
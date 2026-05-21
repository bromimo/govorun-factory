<script setup>
defineOptions({ inheritAttrs: false });

defineProps({
    title: { type: String, default: null },
    text: { type: String, default: null },
    compact: { type: Boolean, default: false },
});
</script>

<template>
    <div class="er-empty" :class="{ compact }" v-bind="$attrs">
        <div v-if="$slots.icon" class="er-empty-ic">
            <slot name="icon" />
        </div>
        <div v-if="title" class="er-empty-h">{{ title }}</div>
        <div v-if="text" class="er-empty-t">{{ text }}</div>
        <div v-if="$slots.action" class="er-empty-act">
            <slot name="action" />
        </div>
    </div>
</template>

<style scoped>
.er-empty {
    text-align: center;
    color: var(--ink-3);
    padding: 32px 16px;
    font-size: 12px;
}
.er-empty.compact { padding: 16px 8px; }
.er-empty-ic {
    margin: 0 auto 8px;
    color: var(--ink-4);
    display: inline-flex;
}
.er-empty-h {
    font-size: 13px;
    color: var(--ink-2);
    font-weight: 600;
    margin-bottom: 4px;
}
.er-empty-t { line-height: 1.4; color: var(--ink-3); }
.er-empty-act { margin-top: 12px; }
</style>
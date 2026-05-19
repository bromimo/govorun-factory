<script setup>
defineOptions({ inheritAttrs: false })

defineProps({
    variant: { type: String, default: 'default' },
    size: { type: String, default: 'md' },
    as: { type: String, default: 'button' },
    type: { type: String, default: 'button' },
    disabled: Boolean,
})
</script>

<template>
    <component
        :is="as"
        v-bind="$attrs"
        :type="as === 'button' ? type : undefined"
        :disabled="disabled"
        class="er-btn"
        :class="{
            'pr': variant === 'primary',
            'dng': variant === 'danger',
            'sm': size === 'sm',
            'lg': size === 'lg',
        }"
    >
        <slot />
    </component>
</template>

<style scoped>
.er-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 0 12px;
    height: 26px;
    border-radius: var(--r-md);
    border: 1px solid var(--bdr-d);
    background: linear-gradient(180deg, #fff 0%, #e2e7ec 100%);
    color: var(--ink);
    font-weight: 500;
    cursor: pointer;
    white-space: nowrap;
    font-family: var(--font);
    font-size: 12px;
    text-decoration: none;
    line-height: 1;
}
.er-btn:hover {
    background: linear-gradient(180deg, #f4f7fa 0%, #d4dbe2 100%);
    border-color: #7a8898;
}
.er-btn:active {
    background: linear-gradient(180deg, #d4dbe2 0%, #e8eef4 100%);
}
.er-btn:disabled {
    opacity: .5;
    cursor: not-allowed;
}
.er-btn.pr {
    background: linear-gradient(180deg, #5a8cd4 0%, #2a5ca0 100%);
    border-color: #1d3a6e;
    color: #fff;
    text-shadow: 0 1px 1px rgba(0,0,0,.2);
}
.er-btn.pr:hover { background: linear-gradient(180deg, #6a9cdc 0%, #3a6cb0 100%); }
.er-btn.dng {
    background: linear-gradient(180deg, #fff 0%, #fae4e4 100%);
    color: var(--red);
    border-color: #c08080;
}
.er-btn.dng:hover { background: linear-gradient(180deg, #fef0f0 0%, #f0c8c8 100%); }
.er-btn.sm { height: 22px; padding: 0 8px; font-size: 11px; }
.er-btn.lg { height: 32px; padding: 0 16px; font-size: 13px; }
</style>
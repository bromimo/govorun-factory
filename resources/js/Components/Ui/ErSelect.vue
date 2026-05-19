<script setup>
defineProps({
    modelValue: [String, Number],
    disabled: Boolean,
    long: Boolean,
})
defineEmits(['update:modelValue'])
</script>

<template>
    <select
        :value="modelValue"
        :disabled="disabled"
        class="er-sel"
        :class="{ long }"
        @change="$emit('update:modelValue', $event.target.value)"
    >
        <slot />
    </select>
</template>

<style scoped>
.er-sel {
    height: 26px;
    padding: 0 8px;
    border: 1px solid var(--bdr-d);
    border-radius: var(--r-sm);
    background: #fff;
    color: var(--ink);
    font-size: 12px;
    font-family: var(--font);
    width: 100%;
    max-width: 380px;
    box-shadow: inset 0 1px 1px rgba(0,0,0,.06);
    cursor: pointer;
}
.er-sel:focus { outline: none; border-color: var(--blue); box-shadow: 0 0 0 2px rgba(58,114,196,.2); }
.er-sel:disabled { background: var(--surface-2); color: var(--ink-3); cursor: not-allowed; }
.er-sel.long { max-width: none; }
</style>
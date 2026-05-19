<script setup>
defineProps({
    modelValue: [String, Number],
    type: { type: String, default: 'text' },
    placeholder: String,
    disabled: Boolean,
    size: { type: String, default: 'md' },
    long: Boolean,
})
defineEmits(['update:modelValue'])
</script>

<template>
    <input
        :type="type"
        :value="modelValue"
        :placeholder="placeholder"
        :disabled="disabled"
        class="er-inp"
        :class="{ sm: size === 'sm', long }"
        @input="$emit('update:modelValue', $event.target.value)"
    />
</template>

<style scoped>
.er-inp {
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
}
.er-inp:focus { outline: none; border-color: var(--blue); box-shadow: 0 0 0 2px rgba(58,114,196,.2); }
.er-inp:disabled { background: var(--surface-2); color: var(--ink-3); cursor: not-allowed; }
.er-inp.sm { height: 22px; font-size: 11px; }
.er-inp.long { max-width: none; }
</style>
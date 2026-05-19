<script setup>
defineProps({
    modelValue: String,
    placeholder: String,
    disabled: Boolean,
    rows: { type: Number, default: 4 },
})
defineEmits(['update:modelValue'])
</script>

<template>
    <textarea
        :value="modelValue"
        :placeholder="placeholder"
        :disabled="disabled"
        :rows="rows"
        class="er-tx"
        @input="$emit('update:modelValue', $event.target.value)"
    ></textarea>
</template>

<style scoped>
.er-tx {
    padding: 6px 8px;
    min-height: 60px;
    line-height: 1.4;
    resize: vertical;
    border: 1px solid var(--bdr-d);
    border-radius: var(--r-sm);
    background: #fff;
    color: var(--ink);
    font-size: 12px;
    font-family: var(--font);
    width: 100%;
    box-shadow: inset 0 1px 1px rgba(0,0,0,.06);
}
.er-tx:focus { outline: none; border-color: var(--blue); box-shadow: 0 0 0 2px rgba(58,114,196,.2); }
.er-tx:disabled { background: var(--surface-2); color: var(--ink-3); }
</style>
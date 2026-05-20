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

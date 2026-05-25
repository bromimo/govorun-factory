<script setup>
const example1 = '{{ user.firstName }}';
const example2 = '{{ name }}';
</script>

<template>
    <p class="vars-hint">
        Переменные оборачивайте в <code class="font-semibold" v-text="'{{ }}'" />:
        <code v-text="example1" />,
        <code v-text="example2" />
    </p>
</template>

<style scoped>
.vars-hint {
    margin-top: 6px;
    border-radius: var(--r-sm);
    padding: 4px 8px;
    font-size: 11px;
    background: var(--orange-soft);
    color: var(--orange);
}
</style>

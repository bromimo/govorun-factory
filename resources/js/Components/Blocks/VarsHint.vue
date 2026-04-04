<script setup>
const example1 = '{{user.firstName}}';
const example2 = '{{name}}';
</script>

<template>
    <p class="mt-2 rounded bg-amber-50 px-2 py-1.5 text-xs text-amber-700">
        Переменные оборачивайте в <code class="font-semibold" v-text="'{{ }}'" />:
        <code v-text="example1" />,
        <code v-text="example2" />
    </p>
</template>

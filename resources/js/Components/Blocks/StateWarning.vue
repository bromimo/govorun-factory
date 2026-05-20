<script setup>
defineProps({
    uninitializedKeys: { type: Array, default: () => [] },
    partiallyInitializedKeys: { type: Array, default: () => [] },
    undeclaredKeys: { type: Array, default: () => [] },
});
</script>

<template>
    <p v-for="k in uninitializedKeys" :key="'u'+k" class="sw-notice sw-notice--warn">
        Нельзя использовать переменную {{ k }} до инициализации
    </p>
    <p v-for="k in partiallyInitializedKeys" :key="'p'+k" class="sw-notice sw-notice--warn">
        Переменная {{ k }} инициализируется не на всех путях — на некоторых ветках может быть пустой
    </p>
    <p v-for="k in undeclaredKeys" :key="'d'+k" class="sw-notice sw-notice--err">
        Переменная {{ k }} не объявлена ни в одном блоке «Сохранить состояние»
    </p>
</template>

<style scoped>
.sw-notice {
    margin-top: 4px;
    border-radius: var(--r-sm);
    padding: 3px 8px;
    font-size: 11px;
}
.sw-notice--warn { background: var(--orange-soft); color: var(--orange); }
.sw-notice--err  { background: var(--red-soft);    color: var(--red);    }
</style>

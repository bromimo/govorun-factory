<script setup>
defineProps({
    uninitializedKeys: { type: Array, default: () => [] },
    partiallyInitializedKeys: { type: Array, default: () => [] },
    undeclaredKeys: { type: Array, default: () => [] },
});
</script>

<template>
    <p v-for="k in uninitializedKeys" :key="'u'+k" class="mt-1 rounded bg-amber-50 px-2 py-1 text-xs text-amber-700">
        Нельзя использовать переменную {{ k }} до инициализации
    </p>
    <p v-for="k in partiallyInitializedKeys" :key="'p'+k" class="mt-1 rounded bg-amber-50 px-2 py-1 text-xs text-amber-700">
        Переменная {{ k }} инициализируется не на всех путях — на некоторых ветках может быть пустой
    </p>
    <p v-for="k in undeclaredKeys" :key="'d'+k" class="mt-1 rounded bg-red-50 px-2 py-1 text-xs text-red-700">
        Переменная {{ k }} не объявлена ни в одном блоке «Сохранить состояние»
    </p>
</template>

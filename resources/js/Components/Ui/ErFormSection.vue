<script setup>
import { ref } from 'vue'

const props = defineProps({
    title: { type: String, required: true },
    collapsible: Boolean,
})

const collapsed = ref(false)
</script>

<template>
    <div class="er-form">
        <div class="er-fset-h">
            <span>{{ title }}</span>
            <button
                v-if="collapsible"
                type="button"
                class="collapse-btn"
                @click="collapsed = !collapsed"
            >
                {{ collapsed ? 'Развернуть' : 'Свернуть' }}
            </button>
        </div>
        <div v-show="!collapsed" class="er-fset-b">
            <slot />
        </div>
    </div>
</template>

<style scoped>
.er-form {
    background: var(--surface);
    border: 1px solid var(--bdr);
    border-radius: var(--r-md);
    margin-bottom: 12px;
}
.er-fset-h {
    padding: 7px 12px;
    font-size: 12px;
    font-weight: 600;
    color: var(--ink);
    background: linear-gradient(180deg, #f4f6f8 0%, #e8ecf0 100%);
    border-bottom: 1px solid var(--bdr);
    border-radius: var(--r-md) var(--r-md) 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.er-fset-b { padding: 12px 16px; }
.collapse-btn {
    font-size: 11px;
    color: var(--blue);
    background: none;
    border: none;
    cursor: pointer;
    padding: 0;
    font-family: var(--font);
}
.collapse-btn:hover { text-decoration: underline; }
</style>
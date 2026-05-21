<script setup>
import { useToast } from '@/composables/useToast';
import ErAlert from './ErAlert.vue';

const { state, dismiss } = useToast();
</script>

<template>
    <div class="er-toast-stack" aria-live="polite">
        <TransitionGroup name="er-toast">
            <ErAlert
                v-for="t in state.items"
                :key="t.id"
                :variant="t.variant"
                :title="t.title"
                closable
                class="er-toast-item"
                @close="dismiss(t.id)"
            >
                {{ t.message }}
            </ErAlert>
        </TransitionGroup>
    </div>
</template>

<style scoped>
.er-toast-stack {
    position: fixed;
    right: 16px;
    bottom: 16px;
    z-index: 9999;
    display: flex;
    flex-direction: column;
    gap: 8px;
    pointer-events: none;
}
.er-toast-item {
    pointer-events: auto;
    min-width: 240px;
    max-width: 360px;
    box-shadow: var(--sh-md);
}
.er-toast-enter-from { opacity: 0; transform: translateY(12px); }
.er-toast-enter-active { transition: opacity .15s, transform .15s; }
.er-toast-leave-to { opacity: 0; transform: translateX(20px); }
.er-toast-leave-active { transition: opacity .15s, transform .15s; }
</style>
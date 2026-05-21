<script setup>
import { useToast } from '@/composables/useToast';
import ErAlert from './ErAlert.vue';

const { state, dismiss } = useToast();
</script>

<template>
    <div class="er-toast-stack" aria-live="polite">
        <TransitionGroup name="er-toast">
            <div
                v-for="t in state.items"
                :key="t.id"
                class="er-toast-item"
            >
                <ErAlert
                    :variant="t.variant"
                    :title="t.title"
                    class="er-toast-alert"
                >
                    {{ t.message }}
                </ErAlert>
                <button
                    type="button"
                    class="er-toast-x"
                    title="Закрыть"
                    @click="dismiss(t.id)"
                >×</button>
            </div>
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
    position: relative;
}
.er-toast-alert {
    box-shadow: var(--sh-md);
    padding-right: 28px;
}
.er-toast-x {
    position: absolute;
    top: 6px;
    right: 6px;
    background: none;
    border: none;
    cursor: pointer;
    color: var(--ink-3);
    font-size: 16px;
    line-height: 1;
    padding: 0 4px;
    min-height: 24px;
}
.er-toast-x:hover {
    color: var(--ink);
}
.er-toast-enter-from { opacity: 0; transform: translateY(12px); }
.er-toast-enter-active { transition: opacity .15s, transform .15s; }
.er-toast-leave-to { opacity: 0; transform: translateX(20px); }
.er-toast-leave-active { transition: opacity .15s, transform .15s; }
</style>
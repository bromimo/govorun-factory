<script setup>
import { onMounted, onUnmounted, watch } from 'vue'
import { X } from 'lucide-vue-next'

const props = defineProps({
    show: { type: Boolean, default: false },
    title: { type: String, default: '' },
    maxWidth: { type: String, default: 'md' },
    closeable: { type: Boolean, default: true },
})

const emit = defineEmits(['close'])

const close = () => {
    if (props.closeable) {
        emit('close')
    }
}

const closeOnEscape = (e) => {
    if (e.key === 'Escape') {
        e.preventDefault()
        if (props.show) close()
    }
}

watch(() => props.show, (val) => {
    document.body.style.overflow = val ? 'hidden' : ''
})

onMounted(() => {
    document.addEventListener('keydown', closeOnEscape)
    if (props.show) {
        document.body.style.overflow = 'hidden'
    }
})

onUnmounted(() => {
    document.removeEventListener('keydown', closeOnEscape)
    document.body.style.overflow = ''
})
</script>

<template>
    <Teleport to="body">
        <Transition name="er-modal">
            <div v-if="show" class="er-modal-root">
                <div class="er-modal-bd" @click="close" />
                <div class="er-modal-box" :class="`mw-${maxWidth.replace('.', '-')}`">
                    <div v-if="title" class="er-modal-hdr">
                        <span class="er-modal-title">{{ title }}</span>
                        <button v-if="closeable" class="er-modal-x" type="button" @click="close">
                            <X :size="14" />
                        </button>
                    </div>
                    <slot />
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.er-modal-root {
    position: fixed;
    inset: 0;
    z-index: 50;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px 16px;
    font-family: var(--font);
    font-size: 12px;
}
.er-modal-bd {
    position: absolute;
    inset: 0;
    background: rgba(26, 34, 48, 0.52);
}
.er-modal-box {
    position: relative;
    z-index: 1;
    width: 100%;
    background: var(--surface);
    border: 1px solid var(--bdr);
    border-radius: var(--r-md);
    box-shadow: var(--sh-md);
    display: flex;
    flex-direction: column;
    max-height: 90vh;
    overflow: hidden;
}
.mw-sm  { max-width: 440px; }
.mw-md  { max-width: 540px; }
.mw-lg  { max-width: 680px; }
.mw-xl  { max-width: 840px; }
.mw-2xl { max-width: 960px; }
.mw-screen { max-width: 92vw; }

.er-modal-hdr {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 12px;
    background: var(--surface-2);
    border-bottom: 1px solid var(--bdr);
    font-size: 12px;
    font-weight: 600;
    color: var(--ink);
}
.er-modal-title {
    font-size: 13px;
    font-weight: 600;
    color: var(--ink);
}
.er-modal-x {
    background: none;
    border: none;
    cursor: pointer;
    padding: 4px;
    min-width: 28px;
    min-height: 28px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: var(--ink-3);
    border-radius: var(--r-sm);
}
.er-modal-x:hover {
    background: var(--surface-2);
    color: var(--ink);
}

.er-modal-enter-active { transition: opacity .2s ease-out; }
.er-modal-leave-active { transition: opacity .15s ease-in; }
.er-modal-enter-from,
.er-modal-leave-to { opacity: 0; }
.er-modal-enter-to,
.er-modal-leave-from { opacity: 1; }
</style>
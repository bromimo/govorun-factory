<script setup>
import Modal from '@/Components/Modal.vue';
import ErButton from '@/Components/Ui/ErButton.vue';

defineProps({
    show:         { type: Boolean, required: true },
    title:        { type: String, default: 'Подтвердите действие' },
    message:      { type: String, default: '' },
    confirmLabel: { type: String, default: 'Удалить' },
    variant:      { type: String, default: 'danger' },
});

const emit = defineEmits(['confirm', 'cancel']);
</script>

<template>
    <Modal :show="show" :title="title" max-width="sm" @close="emit('cancel')">
        <div class="cm-body">
            <p class="cm-msg">{{ message }}</p>
        </div>
        <div class="cm-foot">
            <ErButton @click="emit('cancel')">Отмена</ErButton>
            <ErButton :variant="variant" @click="emit('confirm')">{{ confirmLabel }}</ErButton>
        </div>
    </Modal>
</template>

<style scoped>
.cm-body { padding: 16px 20px; }
.cm-msg { font-size: 12px; color: var(--ink-2); margin: 0; word-break: break-word; }
.cm-foot { display: flex; justify-content: flex-end; gap: 8px; padding: 10px 20px 14px; border-top: 1px solid var(--bdr-l); background: linear-gradient(180deg, #f4f6f8 0%, #e8ecf0 100%); }
</style>
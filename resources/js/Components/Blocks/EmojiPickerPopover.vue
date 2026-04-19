<script setup>
import 'emoji-picker-element';
import { ref } from 'vue';
import { useClickOutside } from '@/utils/useClickOutside';
import { useFloatingPosition } from '@/utils/useFloatingPosition';

const props = defineProps({
    anchor: { type: Object, default: null },
});
const emit = defineEmits(['select', 'close']);

const rootRef = ref(null);
const anchorRef = ref(props.anchor);

const style = useFloatingPosition(() => props.anchor, { width: 348, height: 400 });
useClickOutside(rootRef, () => emit('close'), anchorRef);

function onPick(e) {
    emit('select', e.detail.unicode);
}
</script>

<template>
    <Teleport to="body">
        <div ref="rootRef" :style="style" class="rounded border border-gray-200 bg-white shadow-lg">
            <emoji-picker @emoji-click="onPick"></emoji-picker>
        </div>
    </Teleport>
</template>

import { ref } from 'vue';
import { useVueFlow } from '@vue-flow/core';
import { defaultBlockParams } from '../Blocks/blockTypes.js';

const draggedType = ref(null);

export function useFlowDragDrop() {
    const { screenToFlowCoordinate, addNodes, getNodes } = useVueFlow();

    function onDragStart(event, type) {
        draggedType.value = type;
        event.dataTransfer.effectAllowed = 'move';
    }

    function onDragOver(event) {
        event.preventDefault();
        event.dataTransfer.dropEffect = 'move';
    }

    function onDrop(event) {
        const type = draggedType.value;
        if (!type) return;

        const position = screenToFlowCoordinate({
            x: event.clientX,
            y: event.clientY,
        });

        const id = `${type}_${Date.now()}`;

        addNodes({
            id,
            type,
            position,
            data: { ...defaultBlockParams[type] },
        });

        draggedType.value = null;
    }

    return { onDragStart, onDragOver, onDrop };
}

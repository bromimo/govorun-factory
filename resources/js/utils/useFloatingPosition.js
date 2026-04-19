import { ref, onMounted, onBeforeUnmount } from 'vue';

/** Вычислить fixed-позицию popover'а относительно anchor-элемента.
 * Выравнивает правый край popover'а по правому краю anchor'а и позиционирует снизу.
 * При нехватке места снизу — переключает на позиционирование сверху.
 * Клампит к границам viewport с отступом 8px. Обновляется на resize и scroll.
 *
 * @param {() => HTMLElement | null} getAnchor Геттер DOM-элемента, к которому привязка.
 * @param {{ width: number, height: number }} size Ожидаемые размеры popover'а.
 * @return {import('vue').Ref<Record<string, string>>} Стиль для применения к popover-контейнеру.
 */
export function useFloatingPosition(getAnchor, size) {
    const style = ref({
        position: 'fixed',
        top: '0px',
        left: '0px',
        zIndex: '50',
        visibility: 'hidden',
    });

    function update() {
        const anchor = getAnchor();
        if (!anchor) return;
        const rect = anchor.getBoundingClientRect();
        const vw = window.innerWidth;
        const vh = window.innerHeight;
        const margin = 8;

        let left = rect.right - size.width;
        if (left < margin) left = margin;
        if (left + size.width > vw - margin) left = vw - size.width - margin;

        let top = rect.bottom + 4;
        if (top + size.height > vh - margin) {
            const above = rect.top - size.height - 4;
            if (above >= margin) top = above;
        }
        if (top < margin) top = margin;

        style.value = {
            position: 'fixed',
            top: top + 'px',
            left: left + 'px',
            zIndex: '50',
            visibility: 'visible',
        };
    }

    onMounted(() => {
        update();
        window.addEventListener('resize', update);
        window.addEventListener('scroll', update, true);
    });
    onBeforeUnmount(() => {
        window.removeEventListener('resize', update);
        window.removeEventListener('scroll', update, true);
    });

    return style;
}

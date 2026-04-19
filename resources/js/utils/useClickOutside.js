import { onMounted, onBeforeUnmount } from 'vue';

/** Вызвать callback при клике вне элемента.
 * Слушает mousedown (а не click), чтобы не было гонки с click-обработчиками внутри popover.
 *
 * @param {import('vue').Ref<HTMLElement | null>} elementRef
 * @param {(e: MouseEvent) => void} callback
 */
export function useClickOutside(elementRef, callback) {
    const handler = (e) => {
        if (elementRef.value && !elementRef.value.contains(e.target)) {
            callback(e);
        }
    };
    onMounted(() => document.addEventListener('mousedown', handler));
    onBeforeUnmount(() => document.removeEventListener('mousedown', handler));
}

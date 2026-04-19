import { onMounted, onBeforeUnmount } from 'vue';

/** Вызвать callback при клике вне элемента.
 * Слушает mousedown (а не click), чтобы не было гонки с click-обработчиками внутри popover.
 *
 * @param {import('vue').Ref<HTMLElement | null>} elementRef
 * @param {(e: MouseEvent) => void} callback
 * @param {import('vue').Ref<HTMLElement | null>} [ignoreRef] Дополнительный элемент, клики по которому не считаются «снаружи» (например, кнопка-триггер, которая сама открывает popover).
 */
export function useClickOutside(elementRef, callback, ignoreRef = null) {
    const handler = (e) => {
        const el = elementRef.value;
        const ignore = ignoreRef?.value;
        if (!el) return;
        if (el.contains(e.target)) return;
        if (ignore && ignore.contains(e.target)) return;
        callback(e);
    };
    onMounted(() => document.addEventListener('mousedown', handler));
    onBeforeUnmount(() => document.removeEventListener('mousedown', handler));
}

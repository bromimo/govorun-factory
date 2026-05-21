import { onBeforeUnmount } from 'vue';
import { router } from '@inertiajs/vue3';

/**
 * Сторож для несохранённых изменений формы.
 * Подключает window.beforeunload и Inertia router.before guard,
 * пока isDirtyFn() возвращает true.
 *
 * @param {() => boolean} isDirtyFn — функция-предикат
 * @param {string} [message] — текст подтверждения для internal-навигации
 */
export function useDirtyGuard(isDirtyFn, message = 'Есть несохранённые изменения. Уйти?') {
    function onBeforeUnload(e) {
        if (!isDirtyFn()) return;
        e.preventDefault();
        e.returnValue = '';
    }

    window.addEventListener('beforeunload', onBeforeUnload);

    const removeInertiaGuard = router.on('before', () => {
        if (!isDirtyFn()) return true;
        return window.confirm(message);
    });

    onBeforeUnmount(() => {
        window.removeEventListener('beforeunload', onBeforeUnload);
        removeInertiaGuard();
    });
}
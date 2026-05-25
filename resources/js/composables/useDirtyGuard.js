import { onBeforeUnmount } from 'vue';
import { router } from '@inertiajs/vue3';

/**
 * Сторож для несохранённых изменений формы.
 * Подключает window.beforeunload и Inertia router.before guard,
 * пока isDirtyFn() возвращает true.
 *
 * @param {() => boolean} isDirtyFn — функция-предикат
 * @param {(resume: () => void) => void} onBlocked — вызывается при блокировке навигации;
 *   resume() нужно вызвать если пользователь подтвердил уход
 */
export function useDirtyGuard(isDirtyFn, onBlocked) {
    function onBeforeUnload(e) {
        if (!isDirtyFn()) return;
        e.preventDefault();
        e.returnValue = '';
    }

    window.addEventListener('beforeunload', onBeforeUnload);

    let resuming = false;

    const removeInertiaGuard = router.on('before', (event) => {
        if (resuming) {
            resuming = false;
            return true;
        }
        if (!isDirtyFn()) return true;
        if (event?.detail?.visit?.method !== 'get') return true;

        const url = event.detail.visit.url.href;
        onBlocked(() => {
            resuming = true;
            router.visit(url);
        });
        return false;
    });

    onBeforeUnmount(() => {
        window.removeEventListener('beforeunload', onBeforeUnload);
        removeInertiaGuard();
    });
}
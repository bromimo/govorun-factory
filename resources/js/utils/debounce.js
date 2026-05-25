/**
 * Простой debounce — откладывает вызов fn на delayMs мс,
 * сбрасывая таймер при каждом новом вызове.
 *
 * @param {Function} fn
 * @param {number} delayMs
 * @returns {Function} обёртка с методом cancel()
 */
export function debounce(fn, delayMs) {
    let timerId = null;
    const debounced = function (...args) {
        if (timerId !== null) clearTimeout(timerId);
        timerId = setTimeout(() => {
            timerId = null;
            fn.apply(this, args);
        }, delayMs);
    };
    debounced.cancel = () => {
        if (timerId !== null) {
            clearTimeout(timerId);
            timerId = null;
        }
    };
    return debounced;
}
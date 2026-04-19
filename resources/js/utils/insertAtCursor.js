/** Вставить строку в позицию курсора input/textarea.
 * Сохраняет выделение и триггерит input-событие, чтобы v-model обновился.
 * При отсутствии фокуса (selectionStart === null) — вставка в конец.
 *
 * @param {HTMLInputElement | HTMLTextAreaElement | null} targetEl
 * @param {string} text
 */
export function insertAtCursor(targetEl, text) {
    if (!targetEl) return;
    const start = targetEl.selectionStart ?? targetEl.value.length;
    const end = targetEl.selectionEnd ?? targetEl.value.length;
    const before = targetEl.value.slice(0, start);
    const after = targetEl.value.slice(end);
    targetEl.value = before + text + after;
    const caret = start + text.length;
    targetEl.setSelectionRange(caret, caret);
    targetEl.dispatchEvent(new Event('input', { bubbles: true }));
    targetEl.focus();
}

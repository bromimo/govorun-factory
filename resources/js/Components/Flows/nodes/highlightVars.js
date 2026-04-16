/**
 * Подсветить переменные {{name}} зелёным цветом в тексте.
 * Экранирует HTML, затем оборачивает переменные в <span>.
 */
export function highlightVars(text) {
    if (!text) return '';
    const escaped = text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    return escaped.replace(
        /\{\{(\w+)\}\}/g,
        '<span class="text-green-600 font-medium">{{$1}}</span>',
    );
}

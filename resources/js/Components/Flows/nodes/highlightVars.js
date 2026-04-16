/**
 * Подсветить переменные в тексте.
 * {{name}} — зелёный (пользовательская переменная из state)
 * {{user.firstName}} — синий (свойство объекта message)
 */
export function highlightVars(text) {
    if (!text) return '';
    const escaped = text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    return escaped.replace(/\{\{([\w.]+)\}\}/g, (match, name) => {
        const isDot = name.includes('.');
        const cls = isDot ? 'text-blue-600 font-medium' : 'text-green-600 font-medium';
        return `<span class="${cls}">{{${name}}}</span>`;
    });
}

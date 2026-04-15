import messages from '@resources/validation-messages.json';

export const validationRuleDefs = [
    { name: 'required', label: 'Обязательное', group: 'general', params: [] },
    { name: 'string', label: 'Текст', group: 'type', params: [] },
    { name: 'numeric', label: 'Число', group: 'type', params: [] },
    { name: 'integer', label: 'Целое число', group: 'type', params: [] },
    { name: 'date', label: 'Дата', group: 'type', params: [] },
    { name: 'email', label: 'Email', group: 'format', params: [] },
    { name: 'url', label: 'URL', group: 'format', params: [] },
    { name: 'phone', label: 'Телефон', group: 'format', params: [] },
    { name: 'regex', label: 'Регулярное выражение', group: 'format', params: [{ key: 'pattern', label: 'Паттерн', placeholder: '^[A-Za-z]+$' }] },
    { name: 'min', label: 'Минимум', group: 'range', params: [{ key: 'value', label: 'Значение', type: 'number' }] },
    { name: 'max', label: 'Максимум', group: 'range', params: [{ key: 'value', label: 'Значение', type: 'number' }] },
    { name: 'between', label: 'Диапазон', group: 'range', params: [{ key: 'min', label: 'Мин', type: 'number' }, { key: 'max', label: 'Макс', type: 'number' }] },
    { name: 'in', label: 'Одно из значений', group: 'range', params: [{ key: 'values', label: 'Значения (через запятую)', placeholder: 'да, нет, может быть' }] },
];

export const ruleGroups = [
    { key: 'general', label: 'Общие' },
    { key: 'type', label: 'Тип' },
    { key: 'format', label: 'Формат' },
    { key: 'range', label: 'Диапазон' },
];

export const defaultMessages = messages;

export function getDefaultMessage(ruleName, rules) {
    if ((ruleName === 'min' || ruleName === 'max') && rules.some(r => r.name === 'numeric' || r.name === 'integer')) {
        return messages[ruleName + 'Numeric'] ?? messages[ruleName] ?? '';
    }
    return messages[ruleName] ?? '';
}

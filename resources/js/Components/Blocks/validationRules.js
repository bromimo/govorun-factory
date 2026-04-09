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

export const defaultMessages = {
    required: 'Пожалуйста, введите значение',
    string: 'Значение должно быть текстом',
    numeric: 'Значение должно быть числом',
    integer: 'Значение должно быть целым числом',
    email: 'Введите корректный email',
    url: 'Введите корректный URL',
    phone: 'Введите корректный номер телефона',
    min: 'Минимальная длина: {0} символов',
    max: 'Максимальная длина: {0} символов',
    between: 'Значение должно быть от {0} до {1}',
    in: 'Допустимые значения: {0}',
    regex: 'Значение не соответствует формату',
    date: 'Введите корректную дату',
};

export const numericMessages = {
    min: 'Минимальное значение: {0}',
    max: 'Максимальное значение: {0}',
};

export function getDefaultMessage(ruleName, rules) {
    if ((ruleName === 'min' || ruleName === 'max') && rules.some(r => r.name === 'numeric' || r.name === 'integer')) {
        return numericMessages[ruleName] ?? defaultMessages[ruleName];
    }
    return defaultMessages[ruleName] ?? '';
}

export function formatMessage(template, params) {
    if (!params?.length) return template;
    return params.reduce((msg, val, i) => msg.replace(`{${i}}`, val), template);
}

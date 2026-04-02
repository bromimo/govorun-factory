export const controllerBlockTypes = [
    { type: 'reply_text', label: 'Ответ текстом', color: 'pink' },
    { type: 'reply_keyboard', label: 'Ответ с клавиатурой', color: 'pink' },
    { type: 'save_state', label: 'Сохранить состояние', color: 'green' },
    { type: 'api_call', label: 'API-запрос', color: 'purple' },
];

export const flowNodeTypes = [
    { type: 'ask_text', label: 'Вопрос текстом', color: 'blue' },
    { type: 'ask_keyboard', label: 'Вопрос с клавиатурой', color: 'blue' },
    { type: 'reply_text', label: 'Ответ текстом', color: 'pink' },
    { type: 'reply_keyboard', label: 'Ответ с клавиатурой', color: 'pink' },
    { type: 'save_state', label: 'Сохранить состояние', color: 'green' },
    { type: 'condition', label: 'Условие', color: 'orange' },
    { type: 'api_call', label: 'API-запрос', color: 'purple' },
    { type: 'on_complete', label: 'Завершение', color: 'gray' },
    { type: 'on_cancel', label: 'Отмена', color: 'gray' },
];

export const defaultBlockParams = {
    reply_text: { text: '' },
    reply_keyboard: { text: '', buttons: [] },
    save_state: { key: '', source: '' },
    ask_text: { text: '' },
    ask_keyboard: { text: '', buttons: [] },
    condition: { field: '' },
    api_call: { url: '', method: 'GET' },
    on_complete: {},
    on_cancel: {},
};

export const colorClasses = {
    blue: { bg: 'bg-blue-50', border: 'border-blue-300', text: 'text-blue-800', badge: 'bg-blue-100' },
    pink: { bg: 'bg-pink-50', border: 'border-pink-300', text: 'text-pink-800', badge: 'bg-pink-100' },
    green: { bg: 'bg-green-50', border: 'border-green-300', text: 'text-green-800', badge: 'bg-green-100' },
    orange: { bg: 'bg-orange-50', border: 'border-orange-300', text: 'text-orange-800', badge: 'bg-orange-100' },
    purple: { bg: 'bg-purple-50', border: 'border-purple-300', text: 'text-purple-800', badge: 'bg-purple-100' },
    gray: { bg: 'bg-gray-50', border: 'border-gray-300', text: 'text-gray-800', badge: 'bg-gray-100' },
};

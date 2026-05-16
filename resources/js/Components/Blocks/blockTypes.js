export const controllerBlockTypes = [
    { type: 'reply', label: 'Ответ', color: 'pink' },
    { type: 'save_state', label: 'Сохранить состояние', color: 'green' },
    { type: 'api_call', label: 'API-запрос', color: 'purple' },
];

export const flowNodeTypes = [
    { type: 'ask', label: 'Вопрос', color: 'blue' },
    { type: 'reply', label: 'Ответ', color: 'pink' },
    { type: 'save_state', label: 'Сохранить состояние', color: 'green' },
    { type: 'condition', label: 'Условие', color: 'orange' },
    { type: 'api_call', label: 'API-запрос', color: 'purple' },
    { type: 'on_complete', label: 'Завершение', color: 'gray' },
    { type: 'on_cancel', label: 'Отмена', color: 'gray' },
];

export const defaultBlockParams = {
    reply: { text: '', media: null, keyboard: null },
    ask: { mode: 'text', stepName: '', text: '', media: null, validation: [], keyboard: null },
    save_state: { variables: [{ key: '', source: '' }] },
    condition: { field: '' },
    api_call: {
        connection_id: null,
        method: 'GET',
        path: '',
        headers: [],
        query: [],
        body_mode: 'none',
        body: null,
        response_mapping: [],
        on_error: 'stop_flow',
    },
    on_complete: {},
    on_cancel: {},
    start: {},
};

export function getFlowNodeTypesWithPlugins(plugins = []) {
    const pluginTypes = plugins.map(p => ({
        type: p.name,
        label: p.description || p.name,
        color: 'purple',
        isPlugin: true,
    }));
    return [...flowNodeTypes, ...pluginTypes];
}

export function getControllerBlockTypesWithPlugins(plugins = []) {
    const pluginTypes = plugins.map(p => ({
        type: p.name,
        label: p.description || p.name,
        color: 'purple',
        isPlugin: true,
    }));
    return [...controllerBlockTypes, ...pluginTypes];
}

export const colorClasses = {
    blue: { bg: 'bg-blue-50', border: 'border-blue-300', text: 'text-blue-800', badge: 'bg-blue-100' },
    pink: { bg: 'bg-pink-50', border: 'border-pink-300', text: 'text-pink-800', badge: 'bg-pink-100' },
    green: { bg: 'bg-green-50', border: 'border-green-300', text: 'text-green-800', badge: 'bg-green-100' },
    orange: { bg: 'bg-orange-50', border: 'border-orange-300', text: 'text-orange-800', badge: 'bg-orange-100' },
    purple: { bg: 'bg-purple-50', border: 'border-purple-300', text: 'text-purple-800', badge: 'bg-purple-100' },
    gray: { bg: 'bg-gray-50', border: 'border-gray-300', text: 'text-gray-800', badge: 'bg-gray-100' },
};
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

export const NODE_TYPE_COLORS = {
    ask:         { stripe: '#2a5ca0', fill: '#e8f0fc', gradFrom: '#5a8cd4', gradTo: '#2a5ca0' },
    reply:       { stripe: '#b03030', fill: '#fce4e4', gradFrom: '#d06060', gradTo: '#b03030' },
    save_state:  { stripe: '#3a7a3a', fill: '#e8f4e0', gradFrom: '#6aa860', gradTo: '#3a7a3a' },
    condition:   { stripe: '#c87020', fill: '#fdefd8', gradFrom: '#e0a050', gradTo: '#c87020' },
    api_call:    { stripe: '#7c3aed', fill: '#f0ebff', gradFrom: '#a06aed', gradTo: '#7c3aed' },
    on_complete: { stripe: '#5a6878', fill: '#eef1f4', gradFrom: '#8a9aaa', gradTo: '#5a6878' },
    on_cancel:   { stripe: '#5a6878', fill: '#eef1f4', gradFrom: '#8a9aaa', gradTo: '#5a6878' },
    start:       { stripe: '#5a6878', fill: '#eef1f4', gradFrom: '#8a9aaa', gradTo: '#5a6878' },
};

export const colorClasses = {
    blue: { bg: 'bg-blue-50', border: 'border-blue-300', text: 'text-blue-800', badge: 'bg-blue-100' },
    pink: { bg: 'bg-pink-50', border: 'border-pink-300', text: 'text-pink-800', badge: 'bg-pink-100' },
    green: { bg: 'bg-green-50', border: 'border-green-300', text: 'text-green-800', badge: 'bg-green-100' },
    orange: { bg: 'bg-orange-50', border: 'border-orange-300', text: 'text-orange-800', badge: 'bg-orange-100' },
    purple: { bg: 'bg-purple-50', border: 'border-purple-300', text: 'text-purple-800', badge: 'bg-purple-100' },
    gray: { bg: 'bg-gray-50', border: 'border-gray-300', text: 'text-gray-800', badge: 'bg-gray-100' },
};
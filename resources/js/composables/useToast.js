import { reactive } from 'vue';

const state = reactive({ items: [] });
let idCounter = 0;

function push(variant, message, options = {}) {
    const id = ++idCounter;
    const ttl = options.ttl ?? 4000;
    state.items.push({ id, variant, message, title: options.title ?? null });
    setTimeout(() => dismiss(id), ttl);
    return id;
}

function dismiss(id) {
    const idx = state.items.findIndex((t) => t.id === id);
    if (idx >= 0) state.items.splice(idx, 1);
}

export function useToast() {
    return {
        state,
        success: (msg, opts) => push('success', msg, opts),
        error: (msg, opts) => push('error', msg, opts),
        info: (msg, opts) => push('info', msg, opts),
        warning: (msg, opts) => push('warning', msg, opts),
        dismiss,
    };
}
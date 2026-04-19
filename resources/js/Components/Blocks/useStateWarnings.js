import { computed } from 'vue';

const BUILT_IN = ['user', 'message'];

function readUsedKeys(textInput) {
    const val = typeof textInput === 'function' ? textInput() : textInput?.value;
    if (!val) return [];
    const used = [...val.matchAll(/\{\{\s*(\w+)\s*\}\}/g)].map(m => m[1]);
    return [...new Set(used)].filter(k => !BUILT_IN.some(b => k.startsWith(b)));
}

function readArray(input) {
    const v = typeof input === 'function' ? input() : input?.value;
    return Array.isArray(v) ? v : [];
}

export function useStateWarnings(text, allStateKeys, declaredStateKeys, possiblyDeclaredStateKeys) {
    const used = () => readUsedKeys(text);
    const all = () => readArray(allStateKeys);
    const declared = () => readArray(declaredStateKeys);
    const possibly = () => readArray(possiblyDeclaredStateKeys);

    const uninitializedKeys = computed(() => {
        const u = used();
        if (!u.length) return [];
        const allKeys = all();
        const possiblyKeys = possibly();
        return u.filter(k => allKeys.includes(k) && !possiblyKeys.includes(k));
    });

    const partiallyInitializedKeys = computed(() => {
        const u = used();
        if (!u.length) return [];
        const declaredKeys = declared();
        const possiblyKeys = possibly();
        return u.filter(k => possiblyKeys.includes(k) && !declaredKeys.includes(k));
    });

    const undeclaredKeys = computed(() => {
        const u = used();
        if (!u.length) return [];
        const allKeys = all();
        return u.filter(k => !allKeys.includes(k));
    });

    return { uninitializedKeys, partiallyInitializedKeys, undeclaredKeys };
}

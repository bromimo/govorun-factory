import { computed } from 'vue';

export function useStateWarnings(text, allStateKeys, declaredStateKeys) {
    const uninitializedKeys = computed(() => {
        const val = typeof text === 'function' ? text() : text.value;
        if (!val) return [];

        const all = typeof allStateKeys === 'function' ? allStateKeys() : allStateKeys.value;
        const declared = typeof declaredStateKeys === 'function' ? declaredStateKeys() : declaredStateKeys.value;
        if (!all?.length && !declared?.length) return [];

        const used = [...val.matchAll(/\{\{(\w+)\}\}/g)].map(m => m[1]);
        const builtIn = ['user', 'message'];

        return [...new Set(used)]
            .filter(k => !builtIn.some(b => k.startsWith(b)))
            .filter(k => (all || []).includes(k) && !(declared || []).includes(k));
    });

    const undeclaredKeys = computed(() => {
        const val = typeof text === 'function' ? text() : text.value;
        if (!val) return [];

        const all = typeof allStateKeys === 'function' ? allStateKeys() : allStateKeys.value;
        const declared = typeof declaredStateKeys === 'function' ? declaredStateKeys() : declaredStateKeys.value;

        const used = [...val.matchAll(/\{\{(\w+)\}\}/g)].map(m => m[1]);
        const builtIn = ['user', 'message'];

        return [...new Set(used)]
            .filter(k => !builtIn.some(b => k.startsWith(b)))
            .filter(k => !(all || []).includes(k));
    });

    return { uninitializedKeys, undeclaredKeys };
}

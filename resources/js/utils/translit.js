const translitMap = {
    а:'a',б:'b',в:'v',г:'g',д:'d',е:'e',ё:'yo',ж:'zh',з:'z',и:'i',й:'j',к:'k',
    л:'l',м:'m',н:'n',о:'o',п:'p',р:'r',с:'s',т:'t',у:'u',ф:'f',х:'kh',ц:'ts',
    ч:'ch',ш:'sh',щ:'shch',ъ:'',ы:'y',ь:'',э:'e',ю:'yu',я:'ya',
};

export function translit(str) {
    return (str ?? '').split('').map(c => {
        const lower = c.toLowerCase();
        if (translitMap[lower] !== undefined) {
            const t = translitMap[lower];
            return c === lower ? t : t.charAt(0).toUpperCase() + t.slice(1);
        }
        return c;
    }).join('');
}

/** camelCase-автоген: первое слово строчными, остальные с заглавной. */
export function toCamelCase(str) {
    const ascii = translit(str ?? '');
    const words = ascii.split(/[^a-zA-Z0-9]+/).filter(Boolean);
    return words.map((w, i) => i === 0
        ? w.toLowerCase()
        : w.charAt(0).toUpperCase() + w.slice(1).toLowerCase()
    ).join('');
}

/** PascalCase-автоген: каждое слово с заглавной. */
export function toPascalCase(str) {
    const ascii = translit(str ?? '');
    const words = ascii.split(/[^a-zA-Z0-9]+/).filter(Boolean);
    return words.map(w => w.charAt(0).toUpperCase() + w.slice(1).toLowerCase()).join('');
}

/** Очистка произвольного ввода до латиницы/цифр без ведущей цифры. */
export function sanitizeIdentifier(raw) {
    return (raw ?? '').replace(/[^a-zA-Z0-9]/g, '').replace(/^\d+/, '');
}

/** Определить причину предупреждения для вводимого идентификатора. */
export function identifierWarning(raw) {
    if (/[а-яёА-ЯЁ]/.test(raw)) return 'Только латиница';
    if (/^\d/.test((raw ?? '').replace(/[^a-zA-Z0-9]/g, ''))) return 'Не может начинаться с цифры';
    return '';
}

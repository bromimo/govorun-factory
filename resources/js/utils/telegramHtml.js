/**
 * Сериализовать Tiptap HTML → Telegram-совместимый HTML.
 * Конвертирует <p> → \n\n, <br> → \n, strips disallowed tags.
 * PlaceholderToken <span class="tpl-token"> → {{key}}.
 * @param {string} html
 * @returns {string}
 */
export function serializeTelegramHtml(html) {
    if (!html || html === '<p></p>') return '';

    const div = document.createElement('div');
    div.innerHTML = html;

    return serializeNode(div).replace(/\n\n$/, '').trim();
}

function serializeNode(node) {
    if (node.nodeType === Node.TEXT_NODE) {
        return node.textContent ?? '';
    }

    if (node.nodeType !== Node.ELEMENT_NODE) {
        return '';
    }

    const tag = node.tagName.toLowerCase();

    // PlaceholderToken: <span class="tpl-token">{{key}}</span> → {{key}}
    if (tag === 'span' && node.classList.contains('tpl-token')) {
        return node.textContent ?? '';
    }

    // Paragraph: <p>content</p> → content\n\n
    if (tag === 'p') {
        const content = serializeChildren(node);
        return content ? content + '\n\n' : '';
    }

    // Hard break
    if (tag === 'br') {
        return '\n';
    }

    // Blockquote (optional expandable attribute)
    if (tag === 'blockquote') {
        const content = serializeChildren(node);
        const expandable = node.hasAttribute('expandable') ? ' expandable=""' : '';
        return `<blockquote${expandable}>${content}</blockquote>`;
    }

    // Spoiler span
    if (tag === 'span' && node.classList.contains('tg-spoiler')) {
        return `<span class="tg-spoiler">${serializeChildren(node)}</span>`;
    }

    // Link
    if (tag === 'a') {
        const href = node.getAttribute('href') ?? '';
        if (href) {
            return `<a href="${escapeAttr(href)}">${serializeChildren(node)}</a>`;
        }
        return serializeChildren(node);
    }

    // Code (optionally with language class)
    if (tag === 'code') {
        const cls = node.getAttribute('class') ?? '';
        const clsAttr = cls.startsWith('language-') ? ` class="${escapeAttr(cls)}"` : '';
        return `<code${clsAttr}>${serializeChildren(node)}</code>`;
    }

    // Allowed pass-through tags (no attributes needed)
    const passThrough = new Set(['b', 'strong', 'i', 'em', 'u', 'ins', 's', 'strike', 'del', 'pre']);
    if (passThrough.has(tag)) {
        return `<${tag}>${serializeChildren(node)}</${tag}>`;
    }

    // Everything else: strip tag, keep children
    return serializeChildren(node);
}

function serializeChildren(node) {
    let result = '';
    for (const child of node.childNodes) {
        result += serializeNode(child);
    }
    return result;
}

function escapeAttr(str) {
    return str.replace(/&/g, '&amp;').replace(/"/g, '&quot;');
}

/**
 * Разобрать Telegram HTML → Tiptap-совместимый HTML.
 * \n\n → разрыв параграфа (<p>), \n → <br>.
 * {{key}} → <span class="tpl-token">{{key}}</span> для PlaceholderToken.
 * @param {string} html
 * @returns {string}
 */
export function parseTelegramHtml(html) {
    if (!html) return '<p></p>';

    // Преобразовать {{key}} → tpl-token span
    const withTokens = html.replace(
        /\{\{([\w.]+)\}\}/g,
        '<span class="tpl-token">{{$1}}</span>'
    );

    // Разбить по двойному переводу строки (граница параграфов)
    const paragraphs = withTokens.split('\n\n');

    return paragraphs
        .map(p => `<p>${p.replace(/\n/g, '<br>')}</p>`)
        .join('');
}

/**
 * Снять все HTML-теги, оставив текст и плейсхолдеры {{...}}.
 * Используется для useStateWarnings и autoStepName.
 * @param {string} html
 * @returns {string}
 */
export function stripTelegramHtml(html) {
    if (!html) return '';
    const div = document.createElement('div');
    div.innerHTML = html;
    return div.textContent ?? '';
}
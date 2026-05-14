import { Node } from '@tiptap/core';

export const PlaceholderToken = Node.create({
    name: 'placeholderToken',
    group: 'inline',
    inline: true,
    atom: true,
    selectable: true,
    draggable: false,

    addAttributes() {
        return {
            key: { default: null },
        };
    },

    renderHTML({ node }) {
        return ['span', { class: 'tpl-token' }, `{{${node.attrs.key}}}`];
    },

    parseHTML() {
        return [{
            tag: 'span[class="tpl-token"]',
            getAttrs: (node) => ({
                key: (node.textContent ?? '').replace(/^\{\{|\}\}$/g, ''),
            }),
        }];
    },

    addCommands() {
        return {
            insertPlaceholderToken: (key) => ({ commands }) =>
                commands.insertContent({ type: this.name, attrs: { key } }),
        };
    },
});
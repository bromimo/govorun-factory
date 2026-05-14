import { Node, mergeAttributes } from '@tiptap/core';

export const ExpandableBlockquote = Node.create({
    name: 'blockquote',
    content: 'block+',
    group: 'block',
    defining: true,

    addAttributes() {
        return {
            expandable: {
                default: false,
                parseHTML: element => element.hasAttribute('expandable'),
                renderHTML: (attrs) => attrs.expandable ? { expandable: '' } : {},
            },
        };
    },

    parseHTML() {
        return [{ tag: 'blockquote' }];
    },

    renderHTML({ HTMLAttributes }) {
        return ['blockquote', mergeAttributes(HTMLAttributes), 0];
    },

    addCommands() {
        return {
            setBlockquote: () => ({ commands }) => commands.setNode(this.name),
            toggleBlockquote: () => ({ commands }) =>
                commands.toggleNode(this.name, 'paragraph'),
            unsetBlockquote: () => ({ commands }) => commands.lift(this.name),
            toggleExpandable: () => ({ commands, editor }) => {
                const current = editor.getAttributes(this.name).expandable ?? false;
                return commands.updateAttributes(this.name, { expandable: !current });
            },
        };
    },

    addKeyboardShortcuts() {
        return {
            'Mod-Shift-b': () => this.editor.commands.toggleBlockquote(),
        };
    },
});
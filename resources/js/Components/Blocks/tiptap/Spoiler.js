import { Mark, mergeAttributes } from '@tiptap/core';

export const Spoiler = Mark.create({
    name: 'spoiler',

    renderHTML({ HTMLAttributes }) {
        return ['span', mergeAttributes({ class: 'tg-spoiler' }, HTMLAttributes), 0];
    },

    parseHTML() {
        return [
            { tag: 'span.tg-spoiler' },
            { tag: 'tg-spoiler' },
        ];
    },

    addCommands() {
        return {
            toggleSpoiler: () => ({ commands }) => commands.toggleMark(this.name),
        };
    },
});
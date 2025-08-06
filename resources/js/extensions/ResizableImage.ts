import { Node, mergeAttributes } from '@tiptap/core';
import { VueNodeViewRenderer } from '@tiptap/vue-3';
import ResizableImageView from './ResizableImageView.vue';

export const ResizableImage = Node.create({
    name: 'resizableImage',

    group: 'block',

    atom: true,

    draggable: true,

    addAttributes() {
        return {
            src: {
                default: null,
            },
            alt: {
                default: null,
            },
            width: {
                default: '100%',
            },
        };
    },

    parseHTML() {
        return [
            {
                tag: 'div[data-resizable-image]',
                getAttrs: (dom: HTMLElement) => {
                    const img = dom.querySelector('img');
                    return {
                        src: img?.getAttribute('src'),
                        alt: img?.getAttribute('alt'),
                        width: dom.style.width || '100%',
                    };
                },
            },
        ];
    },

    renderHTML({ HTMLAttributes }) {
        return [
            'div',
            mergeAttributes(HTMLAttributes, {
                'data-resizable-image': '',
                style: `width: ${HTMLAttributes.width}; display: inline-block;`,
            }),
            ['img', { src: HTMLAttributes.src, alt: HTMLAttributes.alt, class: 'img-fluid' }],
        ];
    },

    addNodeView() {
        return VueNodeViewRenderer(ResizableImageView);
    },
});

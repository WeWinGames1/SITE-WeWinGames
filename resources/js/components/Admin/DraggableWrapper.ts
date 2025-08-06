import { defineComponent, h, ref } from 'vue';

export const DraggableWrapper = defineComponent({
    name: 'DraggableWrapper',
    props: {
        modelValue: {
            type: Array,
            required: true,
        },
        tag: {
            type: String,
            default: 'div',
        },
    },
    emits: ['update:modelValue'],
    setup(props, { slots, emit, attrs }) {
        const draggedElement = ref<HTMLElement | null>(null);
        const draggedIndex = ref<number>(-1);
        const dragOverIndex = ref<number>(-1);

        const handleDragStart = (e: DragEvent, index: number) => {
            draggedElement.value = e.target as HTMLElement;
            draggedIndex.value = index;

            if (e.dataTransfer) {
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/html', draggedElement.value.innerHTML);
            }

            draggedElement.value.style.opacity = '0.5';
        };

        const handleDragEnd = (e: DragEvent) => {
            if (draggedElement.value) {
                draggedElement.value.style.opacity = '';
                draggedElement.value = null;
            }
            draggedIndex.value = -1;
            dragOverIndex.value = -1;

            // Remove all drag-over classes
            const wrapper = e.currentTarget as HTMLElement;
            wrapper.querySelectorAll('.drag-over').forEach((el) => {
                el.classList.remove('drag-over');
            });
        };

        const handleDragOver = (e: DragEvent) => {
            if (e.preventDefault) {
                e.preventDefault();
            }
            if (e.dataTransfer) {
                e.dataTransfer.dropEffect = 'move';
            }
            return false;
        };

        const handleDragEnter = (e: DragEvent, index: number) => {
            if (draggedIndex.value !== -1 && draggedIndex.value !== index) {
                const target = e.currentTarget as HTMLElement;
                target.classList.add('drag-over');
                dragOverIndex.value = index;
            }
        };

        const handleDragLeave = (e: DragEvent) => {
            const target = e.currentTarget as HTMLElement;
            target.classList.remove('drag-over');
        };

        const handleDrop = (e: DragEvent, dropIndex: number) => {
            if (e.stopPropagation) {
                e.stopPropagation();
            }

            const target = e.currentTarget as HTMLElement;
            target.classList.remove('drag-over');

            if (draggedIndex.value !== -1 && draggedIndex.value !== dropIndex) {
                const newArray = [...props.modelValue];
                const draggedItem = newArray[draggedIndex.value];

                // Remove the dragged item
                newArray.splice(draggedIndex.value, 1);

                // Insert at new position
                const insertIndex = draggedIndex.value < dropIndex ? dropIndex - 1 : dropIndex;
                newArray.splice(insertIndex, 0, draggedItem);

                emit('update:modelValue', newArray);
            }

            return false;
        };

        return () => {
            const children = slots.default?.() || [];

            // Inject drag handlers into each child
            const modifiedChildren = children.map((child, index) => {
                if (child.type === DraggableItem) {
                    return h(child, {
                        ...child.props,
                        index,
                        onDragstart: (e: DragEvent) => handleDragStart(e, index),
                        onDragend: handleDragEnd,
                        onDragover: handleDragOver,
                        onDragenter: (e: DragEvent) => handleDragEnter(e, index),
                        onDragleave: handleDragLeave,
                        onDrop: (e: DragEvent) => handleDrop(e, index),
                    });
                }
                return child;
            });

            return h(
                props.tag,
                {
                    ...attrs,
                    class: ['draggable-wrapper', attrs.class],
                },
                modifiedChildren,
            );
        };
    },
});

export const DraggableItem = defineComponent({
    name: 'DraggableItem',
    props: {
        index: {
            type: Number,
            default: -1,
        },
    },
    emits: ['dragstart', 'dragend', 'dragover', 'dragenter', 'dragleave', 'drop'],
    setup(props, { slots, emit, attrs }) {
        return () =>
            h(
                'div',
                {
                    ...attrs,
                    class: ['draggable-item', attrs.class],
                    draggable: true,
                    onDragstart: (e: DragEvent) => emit('dragstart', e),
                    onDragend: (e: DragEvent) => emit('dragend', e),
                    onDragover: (e: DragEvent) => emit('dragover', e),
                    onDragenter: (e: DragEvent) => emit('dragenter', e),
                    onDragleave: (e: DragEvent) => emit('dragleave', e),
                    onDrop: (e: DragEvent) => emit('drop', e),
                    style: {
                        cursor: 'move',
                        transition: 'all 0.3s ease',
                        ...((attrs.style as any) || {}),
                    },
                },
                slots.default?.(),
            );
    },
});

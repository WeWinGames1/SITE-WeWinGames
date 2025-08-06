import { Editor } from '@tiptap/core';

export function useImageResize(editor: Editor) {
    // Add event listener for double-click on images
    const handleDoubleClick = (event: MouseEvent) => {
        const target = event.target as HTMLElement;

        // Check if clicked element is an image inside the editor
        if (target.tagName === 'IMG' && target.closest('.ProseMirror')) {
            event.preventDefault();
            event.stopPropagation();

            // Get the parent div wrapper
            const wrapper = target.parentElement;
            if (wrapper && wrapper.style.width) {
                // Show resize prompt
                const currentWidth = wrapper.style.width;
                const selectedOption = prompt(
                    `Current size: ${currentWidth}\n\nSelect new size:\n1. Small (25%)\n2. Medium (50%)\n3. Large (75%)\n4. Full Size (100%)\n\nEnter number (1-4) or percentage (e.g., 60):`,
                    currentWidth.replace('%', ''),
                );

                if (selectedOption) {
                    let newWidth = '100%';
                    switch (selectedOption) {
                        case '1':
                            newWidth = '25%';
                            break;
                        case '2':
                            newWidth = '50%';
                            break;
                        case '3':
                            newWidth = '75%';
                            break;
                        case '4':
                            newWidth = '100%';
                            break;
                        default:
                            const customWidth = parseInt(selectedOption);
                            if (!isNaN(customWidth) && customWidth > 0 && customWidth <= 100) {
                                newWidth = customWidth + '%';
                            }
                    }

                    // Update the wrapper width
                    wrapper.style.width = newWidth;

                    // Trigger editor update to save the change
                    editor.commands.setContent(editor.getHTML());
                }
            }
        }
    };

    // Return function to setup and cleanup listeners
    return {
        setupImageResize: () => {
            const editorElement = editor.view.dom;
            editorElement.addEventListener('dblclick', handleDoubleClick);

            // Return cleanup function
            return () => {
                editorElement.removeEventListener('dblclick', handleDoubleClick);
            };
        },
    };
}

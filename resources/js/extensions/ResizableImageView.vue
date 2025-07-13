<script setup lang="ts">
import { NodeViewWrapper, nodeViewProps } from '@tiptap/vue-3';
import { ref } from 'vue';

const props = defineProps(nodeViewProps);

const showResizeMenu = ref(false);
const menuPosition = ref({ x: 0, y: 0 });

function handleClick(event: MouseEvent) {
    event.preventDefault();
    event.stopPropagation();
    
    const rect = (event.currentTarget as HTMLElement).getBoundingClientRect();
    menuPosition.value = {
        x: rect.left + rect.width / 2,
        y: rect.top,
    };
    showResizeMenu.value = true;
}

function resize(width: string) {
    props.updateAttributes({
        width,
    });
    showResizeMenu.value = false;
}

function handleClickOutside(event: MouseEvent) {
    const target = event.target as HTMLElement;
    if (!target.closest('.resize-menu') && !target.closest('[data-resizable-image]')) {
        showResizeMenu.value = false;
    }
}

// Add global click listener when menu is shown
if (showResizeMenu.value) {
    document.addEventListener('click', handleClickOutside);
} else {
    document.removeEventListener('click', handleClickOutside);
}
</script>

<template>
    <NodeViewWrapper as="div" :style="`width: ${node.attrs.width}; display: inline-block; position: relative;`" data-resizable-image>
        <img 
            :src="node.attrs.src" 
            :alt="node.attrs.alt"
            class="img-fluid resizable-image"
            @click="handleClick"
        />
        
        <!-- Resize Menu -->
        <Teleport to="body" v-if="showResizeMenu">
            <div 
                class="resize-menu"
                :style="`position: fixed; left: ${menuPosition.x}px; top: ${menuPosition.y - 50}px; transform: translateX(-50%); z-index: 9999;`"
            >
                <div class="bg-white border rounded shadow-sm p-2">
                    <div class="d-flex gap-1">
                        <button 
                            @click="resize('25%')" 
                            class="btn btn-sm btn-outline-secondary"
                            title="Small (25%)"
                        >
                            25%
                        </button>
                        <button 
                            @click="resize('50%')" 
                            class="btn btn-sm btn-outline-secondary"
                            title="Medium (50%)"
                        >
                            50%
                        </button>
                        <button 
                            @click="resize('75%')" 
                            class="btn btn-sm btn-outline-secondary"
                            title="Large (75%)"
                        >
                            75%
                        </button>
                        <button 
                            @click="resize('100%')" 
                            class="btn btn-sm btn-outline-secondary"
                            title="Full Size (100%)"
                        >
                            100%
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </NodeViewWrapper>
</template>

<style scoped>
.resizable-image {
    cursor: pointer;
    transition: outline 0.2s ease;
}

.resizable-image:hover {
    outline: 2px solid var(--bs-primary);
    outline-offset: 2px;
}

.resize-menu {
    animation: fadeIn 0.2s ease;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateX(-50%) translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateX(-50%) translateY(0);
    }
}
</style>
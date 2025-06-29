import { defineComponent, h, provide, inject, ref, Ref, computed } from 'vue';

const SidebarContext = Symbol('sidebar');

export function useSidebar() {
  const context = inject<any>(SidebarContext);
  
  return {
    open: computed(() => context?.isOpen.value || false),
    setOpen: (value: boolean) => {
      if (context) context.isOpen.value = value;
    },
    toggleSidebar: () => {
      if (context) context.isOpen.value = !context.isOpen.value;
    }
  };
}

export const Sidebar = defineComponent({
  name: 'Sidebar',
  props: {
    open: Boolean,
    class: [String, Array, Object]
  },
  setup(props, { slots }) {
    const isOpen = ref(props.open || false);
    provide(SidebarContext, isOpen);
    
    const classes = ['sidebar', props.class].filter(Boolean);
    return () => h('aside', { class: classes }, slots.default?.());
  }
});

export const SidebarTrigger = defineComponent({
  name: 'SidebarTrigger',
  props: {
    class: [String, Array, Object]
  },
  setup(props, { slots }) {
    const isOpen = inject<Ref<boolean>>(SidebarContext);
    
    const toggle = () => {
      if (isOpen) isOpen.value = !isOpen.value;
    };
    
    const classes = ['btn btn-link', props.class].filter(Boolean);
    return () => h('button', {
      class: classes,
      onClick: toggle,
      type: 'button'
    }, slots.default?.());
  }
});

export const SidebarInset = defineComponent({
  name: 'SidebarInset',
  props: {
    class: [String, Array, Object]
  },
  setup(props, { slots }) {
    const classes = ['flex-grow-1', props.class].filter(Boolean);
    return () => h('main', { class: classes }, slots.default?.());
  }
});

export const SidebarProvider = defineComponent({
  name: 'SidebarProvider',
  props: {
    class: [String, Array, Object]
  },
  setup(props, { slots }) {
    const classes = ['d-flex', props.class].filter(Boolean);
    return () => h('div', { class: classes }, slots.default?.());
  }
});

export const SidebarGroup = defineComponent({
  name: 'SidebarGroup',
  props: {
    class: [String, Array, Object]
  },
  setup(props, { slots }) {
    const classes = ['sidebar-group mb-3', props.class].filter(Boolean);
    return () => h('div', { class: classes }, slots.default?.());
  }
});

export const SidebarGroupContent = defineComponent({
  name: 'SidebarGroupContent',
  props: {
    class: [String, Array, Object]
  },
  setup(props, { slots }) {
    const classes = ['sidebar-group-content', props.class].filter(Boolean);
    return () => h('div', { class: classes }, slots.default?.());
  }
});

export const SidebarGroupLabel = defineComponent({
  name: 'SidebarGroupLabel',
  props: {
    class: [String, Array, Object]
  },
  setup(props, { slots }) {
    const classes = ['sidebar-group-label text-muted small text-uppercase px-3 mb-2', props.class].filter(Boolean);
    return () => h('div', { class: classes }, slots.default?.());
  }
});

export const SidebarMenu = defineComponent({
  name: 'SidebarMenu',
  props: {
    class: [String, Array, Object]
  },
  setup(props, { slots }) {
    const classes = ['nav flex-column', props.class].filter(Boolean);
    return () => h('ul', { class: classes }, slots.default?.());
  }
});

export const SidebarMenuItem = defineComponent({
  name: 'SidebarMenuItem',
  props: {
    class: [String, Array, Object]
  },
  setup(props, { slots }) {
    const classes = ['nav-item', props.class].filter(Boolean);
    return () => h('li', { class: classes }, slots.default?.());
  }
});

export const SidebarMenuButton = defineComponent({
  name: 'SidebarMenuButton',
  props: {
    asChild: Boolean,
    isActive: Boolean,
    tooltip: String,
    class: [String, Array, Object]
  },
  setup(props, { slots }) {
    const classes = [
      'nav-link',
      props.isActive && 'active',
      props.class
    ].filter(Boolean);
    
    if (props.asChild && slots.default) {
      const child = slots.default()[0];
      return () => h(child, {
        ...child.props,
        class: classes,
        title: props.tooltip
      });
    }
    
    return () => h('a', {
      href: '#',
      class: classes,
      title: props.tooltip
    }, slots.default?.());
  }
});

export const SidebarHeader = defineComponent({
  name: 'SidebarHeader',
  props: {
    class: [String, Array, Object]
  },
  setup(props, { slots }) {
    const classes = ['sidebar-header p-3 border-bottom', props.class].filter(Boolean);
    return () => h('div', { class: classes }, slots.default?.());
  }
});

export const SidebarContent = defineComponent({
  name: 'SidebarContent',
  props: {
    class: [String, Array, Object]
  },
  setup(props, { slots }) {
    const classes = ['sidebar-content p-3', props.class].filter(Boolean);
    return () => h('div', { class: classes }, slots.default?.());
  }
});

export const SidebarFooter = defineComponent({
  name: 'SidebarFooter',
  props: {
    class: [String, Array, Object]
  },
  setup(props, { slots }) {
    const classes = ['sidebar-footer p-3 border-top mt-auto', props.class].filter(Boolean);
    return () => h('div', { class: classes }, slots.default?.());
  }
});

export const SidebarRail = defineComponent({
  name: 'SidebarRail',
  props: {
    class: [String, Array, Object]
  },
  setup(props, { slots }) {
    const classes = ['sidebar-rail', props.class].filter(Boolean);
    return () => h('div', { class: classes }, slots.default?.());
  }
});
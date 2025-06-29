import { defineComponent, h, ref, provide, inject } from 'vue';

const DropdownContext = Symbol('dropdown');

export const DropdownMenu = defineComponent({
  name: 'DropdownMenu',
  setup(props, { slots }) {
    const isOpen = ref(false);
    provide(DropdownContext, { isOpen });
    
    return () => h('div', { class: 'dropdown' }, slots.default?.());
  }
});

export const DropdownMenuTrigger = defineComponent({
  name: 'DropdownMenuTrigger',
  props: {
    asChild: Boolean
  },
  setup(props, { slots }) {
    const dropdown = inject<any>(DropdownContext);
    
    const toggle = () => {
      if (dropdown) dropdown.isOpen.value = !dropdown.isOpen.value;
    };
    
    if (props.asChild && slots.default) {
      const child = slots.default()[0];
      return () => h(child, {
        ...child.props,
        onClick: toggle,
        'data-bs-toggle': 'dropdown'
      });
    }
    
    return () => h('button', {
      class: 'btn dropdown-toggle',
      onClick: toggle,
      'data-bs-toggle': 'dropdown'
    }, slots.default?.());
  }
});

export const DropdownMenuContent = defineComponent({
  name: 'DropdownMenuContent',
  props: {
    align: {
      type: String,
      default: 'start'
    },
    class: [String, Array, Object]
  },
  setup(props, { slots }) {
    const dropdown = inject<any>(DropdownContext);
    const alignClass = props.align === 'end' ? 'dropdown-menu-end' : '';
    const classes = ['dropdown-menu', alignClass, props.class].filter(Boolean);
    
    return () => h('ul', { 
      class: classes,
      style: dropdown?.isOpen.value ? { display: 'block' } : {}
    }, slots.default?.());
  }
});

export const DropdownMenuGroup = defineComponent({
  name: 'DropdownMenuGroup',
  setup(props, { slots }) {
    return () => h('li', {}, slots.default?.());
  }
});

export const DropdownMenuItem = defineComponent({
  name: 'DropdownMenuItem',
  props: {
    class: [String, Array, Object]
  },
  setup(props, { slots }) {
    const classes = ['dropdown-item', props.class].filter(Boolean);
    return () => h('a', { 
      class: classes,
      href: '#',
      onClick: (e: Event) => e.preventDefault()
    }, slots.default?.());
  }
});

export const DropdownMenuLabel = defineComponent({
  name: 'DropdownMenuLabel',
  props: {
    class: [String, Array, Object]
  },
  setup(props, { slots }) {
    const classes = ['dropdown-header', props.class].filter(Boolean);
    return () => h('h6', { class: classes }, slots.default?.());
  }
});

export const DropdownMenuSeparator = defineComponent({
  name: 'DropdownMenuSeparator',
  setup() {
    return () => h('li', {}, h('hr', { class: 'dropdown-divider' }));
  }
});
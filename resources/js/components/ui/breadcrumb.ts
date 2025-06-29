import { defineComponent, h } from 'vue';

export const Breadcrumb = defineComponent({
  name: 'Breadcrumb',
  props: {
    class: [String, Array, Object]
  },
  setup(props, { slots }) {
    const classes = ['breadcrumb', props.class].filter(Boolean);
    return () => h('nav', { 
      'aria-label': 'breadcrumb' 
    }, h('ol', { class: classes }, slots.default?.()));
  }
});

export const BreadcrumbList = defineComponent({
  name: 'BreadcrumbList',
  props: {
    class: [String, Array, Object]
  },
  setup(props, { slots }) {
    const classes = ['breadcrumb', props.class].filter(Boolean);
    return () => h('ol', { class: classes }, slots.default?.());
  }
});

export const BreadcrumbItem = defineComponent({
  name: 'BreadcrumbItem',
  props: {
    active: Boolean,
    class: [String, Array, Object]
  },
  setup(props, { slots }) {
    const classes = [
      'breadcrumb-item',
      props.active && 'active',
      props.class
    ].filter(Boolean);
    
    return () => h('li', { 
      class: classes,
      'aria-current': props.active ? 'page' : undefined
    }, slots.default?.());
  }
});

export const BreadcrumbLink = defineComponent({
  name: 'BreadcrumbLink',
  props: {
    href: String,
    class: [String, Array, Object]
  },
  setup(props, { slots }) {
    const classes = ['text-decoration-none', props.class].filter(Boolean);
    return () => h('a', { 
      href: props.href,
      class: classes 
    }, slots.default?.());
  }
});

export const BreadcrumbSeparator = defineComponent({
  name: 'BreadcrumbSeparator',
  setup(props, { slots }) {
    return () => h('span', { class: 'mx-1' }, slots.default?.() || '/');
  }
});

export const BreadcrumbPage = defineComponent({
  name: 'BreadcrumbPage',
  props: {
    class: [String, Array, Object]
  },
  setup(props, { slots }) {
    return () => h('span', { class: props.class }, slots.default?.());
  }
});
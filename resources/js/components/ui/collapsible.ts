import { defineComponent, h, ref, provide, inject } from 'vue';

const CollapsibleContext = Symbol('collapsible');

export const Collapsible = defineComponent({
  name: 'Collapsible',
  props: {
    open: Boolean,
    class: [String, Array, Object]
  },
  setup(props, { slots }) {
    const isOpen = ref(props.open || false);
    provide(CollapsibleContext, { isOpen });
    
    const classes = ['collapsible', props.class].filter(Boolean);
    return () => h('div', { class: classes }, slots.default?.());
  }
});

export const CollapsibleTrigger = defineComponent({
  name: 'CollapsibleTrigger',
  props: {
    asChild: Boolean,
    class: [String, Array, Object]
  },
  setup(props, { slots }) {
    const collapsible = inject<any>(CollapsibleContext);
    
    const toggle = () => {
      if (collapsible) collapsible.isOpen.value = !collapsible.isOpen.value;
    };
    
    if (props.asChild && slots.default) {
      const child = slots.default()[0];
      return () => h(child, {
        ...child.props,
        onClick: toggle
      });
    }
    
    const classes = ['btn btn-link', props.class].filter(Boolean);
    return () => h('button', {
      class: classes,
      onClick: toggle,
      type: 'button'
    }, slots.default?.());
  }
});

export const CollapsibleContent = defineComponent({
  name: 'CollapsibleContent',
  props: {
    class: [String, Array, Object]
  },
  setup(props, { slots }) {
    const collapsible = inject<any>(CollapsibleContext);
    const classes = ['collapse', collapsible?.isOpen.value && 'show', props.class].filter(Boolean);
    
    return () => h('div', { class: classes }, slots.default?.());
  }
});
import { defineComponent, h } from 'vue';

export const Card = defineComponent({
  name: 'Card',
  props: {
    class: [String, Array, Object]
  },
  setup(props, { slots }) {
    const classes = ['card', props.class].filter(Boolean);
    return () => h('div', { class: classes }, slots.default?.());
  }
});

export const CardHeader = defineComponent({
  name: 'CardHeader',
  props: {
    class: [String, Array, Object]
  },
  setup(props, { slots }) {
    const classes = ['card-header', props.class].filter(Boolean);
    return () => h('div', { class: classes }, slots.default?.());
  }
});

export const CardTitle = defineComponent({
  name: 'CardTitle',
  props: {
    class: [String, Array, Object]
  },
  setup(props, { slots }) {
    const classes = ['card-title h5 mb-0', props.class].filter(Boolean);
    return () => h('h5', { class: classes }, slots.default?.());
  }
});

export const CardDescription = defineComponent({
  name: 'CardDescription',
  props: {
    class: [String, Array, Object]
  },
  setup(props, { slots }) {
    const classes = ['text-muted', props.class].filter(Boolean);
    return () => h('p', { class: classes }, slots.default?.());
  }
});

export const CardContent = defineComponent({
  name: 'CardContent',
  props: {
    class: [String, Array, Object]
  },
  setup(props, { slots }) {
    const classes = ['card-body', props.class].filter(Boolean);
    return () => h('div', { class: classes }, slots.default?.());
  }
});

export const CardFooter = defineComponent({
  name: 'CardFooter',
  props: {
    class: [String, Array, Object]
  },
  setup(props, { slots }) {
    const classes = ['card-footer', props.class].filter(Boolean);
    return () => h('div', { class: classes }, slots.default?.());
  }
});
import { defineComponent, h } from 'vue';

export const Label = defineComponent({
  name: 'Label',
  props: {
    for: String,
    class: [String, Array, Object]
  },
  setup(props, { slots }) {
    const classes = ['form-label', props.class].filter(Boolean);
    return () => h('label', {
      for: props.for,
      class: classes
    }, slots.default?.());
  }
});
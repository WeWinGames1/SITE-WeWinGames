import { defineComponent, h } from 'vue';

export const Separator = defineComponent({
  name: 'Separator',
  props: {
    orientation: {
      type: String,
      default: 'horizontal'
    },
    class: [String, Array, Object]
  },
  setup(props, { slots }) {
    const classes = [
      props.orientation === 'vertical' ? 'vr' : 'hr',
      props.class
    ].filter(Boolean);
    
    return () => h(props.orientation === 'vertical' ? 'div' : 'hr', { 
      class: classes 
    });
  }
});
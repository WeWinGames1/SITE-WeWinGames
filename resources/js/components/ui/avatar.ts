import { defineComponent, h } from 'vue';

export const Avatar = defineComponent({
  name: 'Avatar',
  props: {
    size: {
      type: String,
      default: 'md'
    },
    class: [String, Array, Object]
  },
  setup(props, { slots }) {
    const sizeClasses = {
      sm: 'avatar-sm',
      md: 'avatar-md',
      lg: 'avatar-lg'
    };
    
    const classes = [
      'avatar rounded-circle',
      sizeClasses[props.size] || sizeClasses.md,
      props.class
    ].filter(Boolean);
    
    return () => h('div', { class: classes }, slots.default?.());
  }
});

export const AvatarImage = defineComponent({
  name: 'AvatarImage',
  props: {
    src: String,
    alt: String,
    class: [String, Array, Object]
  },
  setup(props) {
    const classes = ['img-fluid rounded-circle', props.class].filter(Boolean);
    return () => h('img', {
      src: props.src,
      alt: props.alt,
      class: classes
    });
  }
});

export const AvatarFallback = defineComponent({
  name: 'AvatarFallback',
  props: {
    class: [String, Array, Object]
  },
  setup(props, { slots }) {
    const classes = ['avatar-fallback d-flex align-items-center justify-content-center bg-secondary text-white rounded-circle h-100 w-100', props.class].filter(Boolean);
    return () => h('div', { class: classes }, slots.default?.());
  }
});
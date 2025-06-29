import { defineComponent, h } from 'vue';

export const Button = defineComponent({
  name: 'Button',
  props: {
    type: {
      type: String,
      default: 'button'
    },
    variant: {
      type: String,
      default: 'primary'
    },
    size: {
      type: String,
      default: 'md'
    },
    disabled: Boolean,
    class: [String, Array, Object]
  },
  setup(props, { slots }) {
    const variantClasses = {
      primary: 'btn-primary',
      secondary: 'btn-secondary',
      danger: 'btn-danger',
      success: 'btn-success',
      warning: 'btn-warning',
      info: 'btn-info',
      light: 'btn-light',
      dark: 'btn-dark',
      link: 'btn-link',
      outline: 'btn-outline-primary'
    };

    const sizeClasses = {
      sm: 'btn-sm',
      md: '',
      lg: 'btn-lg'
    };

    const classes = [
      'btn',
      variantClasses[props.variant] || variantClasses.primary,
      sizeClasses[props.size] || '',
      props.class
    ].filter(Boolean);

    return () => h('button', {
      type: props.type,
      disabled: props.disabled,
      class: classes
    }, slots.default?.());
  }
});
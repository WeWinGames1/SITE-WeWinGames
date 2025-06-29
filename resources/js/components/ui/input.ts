import { defineComponent, h } from 'vue';

export const Input = defineComponent({
  name: 'Input',
  props: {
    type: {
      type: String,
      default: 'text'
    },
    modelValue: [String, Number],
    placeholder: String,
    disabled: Boolean,
    required: Boolean,
    autofocus: Boolean,
    autocomplete: String,
    id: String,
    name: String,
    class: [String, Array, Object]
  },
  emits: ['update:modelValue'],
  setup(props, { emit }) {
    const classes = [
      'form-control',
      props.class
    ].filter(Boolean);

    return () => h('input', {
      type: props.type,
      value: props.modelValue,
      placeholder: props.placeholder,
      disabled: props.disabled,
      required: props.required,
      autofocus: props.autofocus,
      autocomplete: props.autocomplete,
      id: props.id,
      name: props.name,
      class: classes,
      onInput: (e: Event) => emit('update:modelValue', (e.target as HTMLInputElement).value)
    });
  }
});
import { defineComponent, h, ref, provide, inject } from 'vue';

const DialogContext = Symbol('dialog');

export const Dialog = defineComponent({
  name: 'Dialog',
  props: {
    open: Boolean,
  },
  emits: ['update:open'],
  setup(props, { slots, emit }) {
    const isOpen = ref(props.open || false);
    
    const setOpen = (value: boolean) => {
      isOpen.value = value;
      emit('update:open', value);
    };
    
    provide(DialogContext, { isOpen, setOpen });
    
    return () => slots.default?.({ open: isOpen.value, setOpen });
  }
});

export const DialogTrigger = defineComponent({
  name: 'DialogTrigger',
  props: {
    asChild: Boolean
  },
  setup(props, { slots }) {
    const dialog = inject<any>(DialogContext);
    
    const open = () => {
      if (dialog) dialog.setOpen(true);
    };
    
    if (props.asChild && slots.default) {
      const child = slots.default()[0];
      return () => h(child, {
        ...child.props,
        onClick: open,
        'data-bs-toggle': 'modal'
      });
    }
    
    return () => h('button', {
      class: 'btn btn-primary',
      onClick: open,
      'data-bs-toggle': 'modal'
    }, slots.default?.());
  }
});

export const DialogContent = defineComponent({
  name: 'DialogContent',
  props: {
    class: [String, Array, Object]
  },
  setup(props, { slots }) {
    const dialog = inject<any>(DialogContext);
    const classes = ['modal-dialog', props.class].filter(Boolean);
    
    return () => h('div', {
      class: ['modal fade', dialog?.isOpen.value && 'show'],
      style: dialog?.isOpen.value ? { display: 'block' } : {},
      tabindex: '-1'
    }, 
      h('div', { class: classes },
        h('div', { class: 'modal-content' }, slots.default?.())
      )
    );
  }
});

export const DialogHeader = defineComponent({
  name: 'DialogHeader',
  props: {
    class: [String, Array, Object]
  },
  setup(props, { slots }) {
    const classes = ['modal-header', props.class].filter(Boolean);
    return () => h('div', { class: classes }, slots.default?.());
  }
});

export const DialogTitle = defineComponent({
  name: 'DialogTitle',
  props: {
    class: [String, Array, Object]
  },
  setup(props, { slots }) {
    const classes = ['modal-title h5', props.class].filter(Boolean);
    return () => h('h5', { class: classes }, slots.default?.());
  }
});

export const DialogDescription = defineComponent({
  name: 'DialogDescription',
  props: {
    class: [String, Array, Object]
  },
  setup(props, { slots }) {
    const classes = ['text-muted', props.class].filter(Boolean);
    return () => h('p', { class: classes }, slots.default?.());
  }
});

export const DialogClose = defineComponent({
  name: 'DialogClose',
  setup(props, { slots }) {
    const dialog = inject<any>(DialogContext);
    
    const close = () => {
      if (dialog) dialog.setOpen(false);
    };
    
    return () => h('button', {
      type: 'button',
      class: 'btn-close',
      onClick: close,
      'data-bs-dismiss': 'modal'
    });
  }
});

export const DialogFooter = defineComponent({
  name: 'DialogFooter',
  props: {
    class: [String, Array, Object]
  },
  setup(props, { slots }) {
    const classes = ['modal-footer', props.class].filter(Boolean);
    return () => h('div', { class: classes }, slots.default?.());
  }
});
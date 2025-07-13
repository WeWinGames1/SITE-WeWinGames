<script setup lang="ts">
import { useToast } from '@/composables/useToast';

const { toasts, removeToast } = useToast();

function getToastClasses(type: string): string {
  const baseClasses = 'toast show';
  switch (type) {
    case 'success':
      return `${baseClasses} text-white bg-primary border-primary`;
    case 'error':
      return `${baseClasses} border-danger`;
    case 'warning':
      return `${baseClasses} border-warning`;
    case 'info':
      return `${baseClasses} border-info`;
    default:
      return `${baseClasses} border-primary`;
  }
}

function getIconClass(type: string): string {
  switch (type) {
    case 'success':
      return 'bi-check-circle-fill text-white';
    case 'error':
      return 'bi-exclamation-triangle-fill text-danger';
    case 'warning':
      return 'bi-exclamation-triangle-fill text-warning';
    case 'info':
      return 'bi-info-circle-fill text-info';
    default:
      return 'bi-info-circle-fill text-primary';
  }
}
</script>

<template>
  <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999;">
    <div
      v-for="toast in toasts"
      :key="toast.id"
      :class="getToastClasses(toast.type)"
      role="alert"
      aria-live="assertive"
      aria-atomic="true"
    >
      <div :class="['toast-header', toast.type === 'success' ? 'text-white bg-primary border-primary' : '']">
        <i :class="['bi', getIconClass(toast.type), 'me-2']"></i>
        <strong class="me-auto">{{ toast.type.charAt(0).toUpperCase() + toast.type.slice(1) }}</strong>
        <button
          type="button"
          :class="['btn-close', toast.type === 'success' ? 'btn-close-white' : '']"
          @click="removeToast(toast.id)"
          aria-label="Close"
        ></button>
      </div>
      <div :class="['toast-body', toast.type === 'success' ? 'text-white' : '']">
        {{ toast.message }}
      </div>
    </div>
  </div>
</template>

<style scoped>
.toast-container {
  max-width: 350px;
}

.toast {
  margin-bottom: 0.5rem;
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

@media (max-width: 768px) {
  .toast-container {
    left: 1rem;
    right: 1rem;
    max-width: none;
  }
}
</style>
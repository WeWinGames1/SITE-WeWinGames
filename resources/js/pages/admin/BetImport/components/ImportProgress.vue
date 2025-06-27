<template>
  <nav aria-label="Progress">
    <ol class="flex items-center">
      <li v-for="(step, index) in steps" :key="step.number" class="relative flex-1">
        <div
          class="flex items-center"
          :class="{ 'cursor-pointer': step.number < currentStep }"
          @click="step.number < currentStep && $emit('go-to-step', step.number)"
        >
          <!-- Connector line -->
          <div
            v-if="index !== 0"
            class="absolute left-0 top-5 -ml-px h-0.5 w-full"
            :class="{
              'bg-indigo-600': step.number < currentStep,
              'bg-gray-300': step.number >= currentStep
            }"
          />

          <!-- Step circle -->
          <div class="relative flex h-10 w-10 items-center justify-center rounded-full"
               :class="{
                 'bg-indigo-600 text-white': step.number <= currentStep,
                 'bg-white border-2 border-gray-300 text-gray-500': step.number > currentStep
               }">
            <CheckIcon v-if="step.number < currentStep" class="h-5 w-5" />
            <span v-else>{{ step.number }}</span>
          </div>

          <!-- Step info -->
          <div class="ml-3">
            <p class="text-sm font-medium"
               :class="{
                 'text-indigo-600': step.number === currentStep,
                 'text-gray-900': step.number < currentStep,
                 'text-gray-500': step.number > currentStep
               }">
              {{ step.name }}
            </p>
            <p class="text-xs text-gray-500">{{ step.description }}</p>
          </div>
        </div>
      </li>
    </ol>
  </nav>
</template>

<script setup lang="ts">
import { CheckIcon } from '@heroicons/vue/24/solid'

interface Step {
  number: number
  name: string
  description: string
}

interface Props {
  currentStep: number
  steps: Step[]
}

defineProps<Props>()
defineEmits<{
  'go-to-step': [step: number]
}>()
</script>
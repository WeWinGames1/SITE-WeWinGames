<template>
    <nav aria-label="Progress">
        <div class="d-flex justify-content-between position-relative">
            <!-- Progress line background -->
            <div class="position-absolute w-100" style="top: 20px; height: 2px; background-color: #e9ecef; z-index: 0"></div>

            <!-- Progress line fill -->
            <div
                class="position-absolute"
                :style="{
                    top: '20px',
                    height: '2px',
                    width: `${((currentStep - 1) / (steps.length - 1)) * 100}%`,
                    backgroundColor: '#0d6efd',
                    transition: 'width 0.3s ease',
                    zIndex: 1,
                }"
            ></div>

            <!-- Steps -->
            <div
                v-for="(step, index) in steps"
                :key="step.number"
                class="d-flex flex-column align-items-center position-relative"
                :class="{ 'cursor-pointer': step.number < currentStep }"
                @click="step.number < currentStep && $emit('go-to-step', step.number)"
                style="z-index: 2"
            >
                <!-- Step circle -->
                <div
                    class="rounded-circle d-flex align-items-center justify-content-center"
                    :class="{
                        'bg-primary text-white': step.number <= currentStep,
                        'bg-white border border-2': step.number > currentStep,
                    }"
                    :style="{
                        width: '40px',
                        height: '40px',
                        borderColor: step.number > currentStep ? '#dee2e6' : undefined,
                    }"
                >
                    <i v-if="step.number < currentStep" class="bi bi-check"></i>
                    <span v-else>{{ step.number }}</span>
                </div>

                <!-- Step info -->
                <div class="text-center mt-2">
                    <p
                        class="mb-0 small fw-medium"
                        :class="{
                            'text-primary': step.number === currentStep,
                            'text-dark': step.number < currentStep,
                            'text-secondary': step.number > currentStep,
                        }"
                    >
                        {{ step.name }}
                    </p>
                    <p class="mb-0 text-dark" style="font-size: 0.75rem">{{ step.description }}</p>
                </div>
            </div>
        </div>
    </nav>
</template>

<script setup lang="ts">
interface Step {
    number: number;
    name: string;
    description: string;
}

interface Props {
    currentStep: number;
    steps: Step[];
}

defineProps<Props>();
defineEmits<{
    'go-to-step': [step: number];
}>();
</script>

<style scoped>
.cursor-pointer {
    cursor: pointer;
}
</style>

<script setup lang="ts">
defineProps<{
  plans: Array<{
    name: string,
    price: string,
    duration: string,
    features: string[],
    monthlyLink: string,
    weeklyLink?: string,
    dailyLink?: string,
    weeklyPrice?: string,
    dailyPrice?: string,
    highlight?: boolean,
  }>
}>();
</script>

<template>
  <div class="row g-4 justify-content-center mb-5">
    <div
      v-for="plan in plans"
      :key="plan.name"
      class="col-lg-4"
    >
      <div 
        :class="[
          'card h-100 position-relative',
          plan.highlight ? 'pricing-card-highlight' : ''
        ]"
        style="background-color: var(--bs-card-bg); border: 1px solid var(--bs-card-border);"
      >
        <div class="card-body d-flex flex-column p-5">
          <!-- Plan Header -->
          <div class="mb-4">
            <h3 class="fw-bold text-white mb-2">{{ plan.name }}</h3>
            <p class="text-gray-light mb-0">
              {{ plan.highlight ? 'Most popular choice' : plan.name === 'Platinum' ? 'Maximum value' : 'Great for beginners' }}
            </p>
          </div>
          
          <!-- Pricing -->
          <div class="mb-5">
            <div class="d-flex align-items-baseline mb-3">
              <span class="display-3 fw-bold text-white">{{ plan.price }}</span>
              <span class="text-gray-light ms-2">/ month</span>
            </div>
            <div class="d-flex flex-wrap gap-3 text-gray-light small">
              <span><i class="bi bi-check text-success"></i> Weekly: ${{ plan.weeklyPrice }}</span>
              <span><i class="bi bi-check text-success"></i> Daily: ${{ plan.dailyPrice }}</span>
            </div>
          </div>
          
          <!-- Features -->
          <ul class="list-unstyled mb-5 flex-grow-1">
            <li v-for="feature in plan.features" :key="feature" class="mb-3 d-flex align-items-start">
              <i class="bi bi-check-circle-fill text-purple me-3 flex-shrink-0" style="margin-top: 2px;"></i>
              <span class="text-gray-light">{{ feature }}</span>
            </li>
          </ul>
          
          <!-- CTA Buttons -->
          <div class="mt-auto">
            <a
              :href="plan.monthlyLink"
              class="btn btn-lg w-100 py-3 mb-3"
              :class="plan.highlight ? 'btn-primary' : 'btn-outline-primary'"
            >
              <span class="fw-semibold">{{ plan.highlight ? 'Start Free Trial' : 'Get Started' }}</span>
              <i class="bi bi-arrow-right ms-2"></i>
            </a>
            
            <!-- Additional Billing Options -->
            <div class="text-center">
              <small class="text-gray-light">
                Also available: 
                <a :href="plan.weeklyLink" class="text-purple text-decoration-none">Weekly</a> • 
                <a :href="plan.dailyLink" class="text-purple text-decoration-none">Daily</a>
              </small>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
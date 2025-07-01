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
  <div class="row g-3 justify-content-center">
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
        <div class="card-body d-flex flex-column p-4">
          <!-- Plan Header -->
          <div class="mb-3 text-center">
            <img 
              :src="`/images/plan-icon-${plan.name.toLowerCase()}.svg`" 
              :alt="`${plan.name} Plan`" 
              class="mb-2"
              style="height: 45px; width: auto;"
            >
            <h3 class="h4 fw-bold text-white mb-1">{{ plan.name }}</h3>
            <p class="text-gray-light mb-0 small">
              {{ plan.highlight ? 'Most popular choice' : plan.name === 'Platinum' ? 'Maximum value' : 'Great for beginners' }}
            </p>
          </div>
          
          <!-- Pricing -->
          <div class="mb-3">
            <div class="d-flex align-items-baseline mb-2">
              <span class="display-5 fw-bold text-white">{{ plan.price }}</span>
              <span class="text-gray-light ms-2">/ month</span>
            </div>
            <div class="d-flex flex-wrap gap-3 text-gray-light small">
              <span><i class="bi bi-check text-success"></i> Weekly: ${{ plan.weeklyPrice }}</span>
              <span><i class="bi bi-check text-success"></i> Daily: ${{ plan.dailyPrice }}</span>
            </div>
          </div>
          
          <!-- Features -->
          <ul class="list-unstyled mb-3 flex-grow-1">
            <li v-for="feature in plan.features" :key="feature" class="mb-2 d-flex align-items-start">
              <i class="bi bi-check-circle-fill text-purple me-2 flex-shrink-0 small" style="margin-top: 2px;"></i>
              <span class="text-gray-light small">{{ feature }}</span>
            </li>
          </ul>
          
          <!-- CTA Buttons -->
          <div class="mt-auto">
            <a
              :href="plan.monthlyLink"
              class="btn w-100 py-2 mb-2"
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
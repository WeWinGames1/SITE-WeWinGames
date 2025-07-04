<script setup lang="ts">
interface CurrentPlan {
    tier: string;
    period: string;
    price_id: string;
}

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
    isCurrentPlan?: boolean,
  }>,
  currentPlan?: CurrentPlan | null
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
          plan.highlight ? 'border-primary border-2' : '',
          plan.isCurrentPlan ? 'border-success border-2' : ''
        ]"
      >
        <!-- Current Plan Badge -->
        <div v-if="plan.isCurrentPlan" class="position-absolute top-0 start-50 translate-middle">
          <span class="badge bg-success px-3 py-2">Current Plan</span>
        </div>
        
        <div class="card-body d-flex flex-column p-4">
          <!-- Plan Header -->
          <div class="mb-3 text-center">
            <h3 class="h4 fw-bold mb-1">{{ plan.name }}</h3>
            <p class="text-muted mb-0 small">
              {{ plan.highlight ? 'Most popular choice' : plan.name === 'Platinum' ? 'Maximum value' : 'Great for beginners' }}
            </p>
          </div>
          
          <!-- Pricing -->
          <div class="mb-3">
            <div class="d-flex align-items-baseline mb-2 justify-content-center">
              <span class="display-5 fw-bold">{{ plan.price }}</span>
              <span class="text-muted ms-2">/ month</span>
            </div>
            <div class="d-flex flex-wrap gap-3 text-muted small justify-content-center">
              <span><i class="bi bi-check text-success"></i> Weekly: ${{ plan.weeklyPrice }}</span>
              <span><i class="bi bi-check text-success"></i> Daily: ${{ plan.dailyPrice }}</span>
            </div>
          </div>
          
          <!-- Features -->
          <ul class="list-unstyled mb-3 flex-grow-1">
            <li v-for="feature in plan.features" :key="feature" class="mb-2 d-flex align-items-start">
              <i class="bi bi-check-circle-fill text-success me-2 flex-shrink-0 small" style="margin-top: 2px;"></i>
              <span class="small">{{ feature }}</span>
            </li>
          </ul>
          
          <!-- CTA Buttons -->
          <div class="mt-auto">
            <a
              v-if="!plan.isCurrentPlan"
              :href="plan.monthlyLink"
              class="btn w-100 py-2 mb-2"
              :class="plan.highlight ? 'btn-primary' : 'btn-outline-primary'"
            >
              <span class="fw-semibold">{{ currentPlan ? 'Switch to ' + plan.name : 'Get Started' }}</span>
              <i class="bi bi-arrow-right ms-2"></i>
            </a>
            <div v-else class="btn btn-success w-100 py-2 mb-2 disabled">
              <i class="bi bi-check-circle me-2"></i>
              <span class="fw-semibold">Your Current Plan</span>
            </div>
            
            <!-- Additional Billing Options -->
            <div class="text-center">
              <small class="text-muted">
                Also available: 
                <a :href="plan.weeklyLink" class="text-primary text-decoration-none">Weekly</a> • 
                <a :href="plan.dailyLink" class="text-primary text-decoration-none">Daily</a>
              </small>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
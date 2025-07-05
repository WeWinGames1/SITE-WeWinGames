<script setup lang="ts">
import { ref } from 'vue';

interface CurrentPlan {
    tier: string;
    period: string;
    price_id: string;
}

interface Plan {
    name: string;
    price: string;
    monthlyPrice: string;
    weeklyPrice: string;
    dailyPrice: string;
    duration: string;
    features: string[];
    monthlyLink: string;
    weeklyLink: string;
    dailyLink: string;
    highlight?: boolean;
    isCurrentPlan?: boolean;
}

defineProps<{
  plans: Array<Plan>,
  currentPlan?: CurrentPlan | null
}>();

const selectedPeriod = ref<'monthly' | 'weekly' | 'daily'>('monthly');
</script>

<template>
  <div>
    <!-- Billing Period Selector -->
    <div class="text-center mb-4">
      <div class="btn-group" role="group" aria-label="Billing period">
        <button 
          type="button" 
          class="btn" 
          :class="selectedPeriod === 'monthly' ? 'btn-primary' : 'btn-outline-primary'"
          @click="selectedPeriod = 'monthly'"
        >
          Monthly
        </button>
        <button 
          type="button" 
          class="btn" 
          :class="selectedPeriod === 'weekly' ? 'btn-primary' : 'btn-outline-primary'"
          @click="selectedPeriod = 'weekly'"
        >
          Weekly
        </button>
        <button 
          type="button" 
          class="btn" 
          :class="selectedPeriod === 'daily' ? 'btn-primary' : 'btn-outline-primary'"
          @click="selectedPeriod = 'daily'"
        >
          Daily
        </button>
      </div>
    </div>
    
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
        
        <!-- 50% Off First Month Special for All Plans -->
        <div v-else-if="selectedPeriod === 'monthly'" class="position-absolute top-0 start-50 translate-middle">
          <span class="badge bg-warning text-dark px-3 py-2">
            <i class="bi bi-star-fill me-1"></i>
            50% Off First Month
          </span>
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
              <span class="display-5 fw-bold">
                {{ selectedPeriod === 'monthly' ? plan.monthlyPrice : 
                   selectedPeriod === 'weekly' ? '$' + plan.weeklyPrice : 
                   '$' + plan.dailyPrice }}
              </span>
              <span class="text-muted ms-2">/ {{ selectedPeriod === 'monthly' ? 'month' : selectedPeriod === 'weekly' ? 'week' : 'day' }}</span>
            </div>
            <div class="text-center text-muted small">
              <span v-if="selectedPeriod === 'monthly'" class="text-warning fw-bold">
                <i class="bi bi-star-fill"></i> 
                <template v-if="plan.name === 'Platinum'">Special: $40 first month, then $80/month</template>
                <template v-else-if="plan.name === 'Gold'">Special: $32.50 first month, then $65/month</template>
                <template v-else-if="plan.name === 'Silver'">Special: $22.50 first month, then $45/month</template>
              </span>
              <span v-else-if="selectedPeriod === 'weekly'">
                ${{ plan.monthlyPrice }}/month • ${{ plan.dailyPrice }}/day
              </span>
              <span v-else>
                ${{ plan.monthlyPrice }}/month • ${{ plan.weeklyPrice }}/week
              </span>
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
              :href="selectedPeriod === 'monthly' ? plan.monthlyLink : 
                     selectedPeriod === 'weekly' ? plan.weeklyLink : 
                     plan.dailyLink"
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
            
          </div>
        </div>
      </div>
    </div>
    </div>
  </div>
</template>
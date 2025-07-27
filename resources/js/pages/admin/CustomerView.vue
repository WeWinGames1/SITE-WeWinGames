<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { route } from 'ziggy-js';

interface Props {
  customer: {
    id: number;
    name: string;
    email: string;
    created_at: string;
    status: 'active' | 'disabled' | 'pending';
    stripe_id?: string;
    is_ambassador?: boolean;
    is_gifted?: boolean;
    admin_override?: boolean;
    override_tier?: string;
    override_expiry?: string;
  };
  paymentMethods: Array<{
    id: string;
    brand: string;
    last4: string;
    exp_month: number;
    exp_year: number;
    is_default: boolean;
  }>;
  activities: Array<{
    id: number;
    description: string;
    properties: any;
    causer: {
      id: number;
      name: string;
      email: string;
    } | null;
    created_at: string;
  }>;
  subscriptionHistory: Array<{
    id: number;
    stripe_id: string;
    status: string;
    price_id: string;
    tier?: string;
    interval?: string;
    trial_ends_at?: string;
    current_period_start?: string;
    current_period_end?: string;
    ends_at?: string;
    created_at: string;
    is_manual: boolean;
  }>;
  currentSubscription?: {
    status: string;
    price_id: string;
    trial_ends_at?: string;
    current_period_end?: string;
    ends_at?: string;
    is_manual: boolean;
    days_until_renewal?: number;
  };
  invoices: Array<{
    id: string;
    number: string;
    total: number;
    status: string;
    created_at: string;
    hosted_invoice_url: string;
    invoice_pdf: string;
  }>;
  discountUsage: Array<{
    code: string;
    amount: number;
    type: string;
    subscription_id: string;
    created_at: string;
  }>;
  stats: {
    total_spent: number;
    subscription_count: number;
    days_as_customer: number;
  };
}

const props = defineProps<Props>();

const activeTab = ref('overview');
const isLoading = ref(false);
const showGrantModal = ref(false);

function formatDate(date: string | null | undefined) {
  if (!date) return 'N/A';
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
}

function formatCurrency(cents: number) {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(cents / 100);
}

function getStatusBadgeClass(status: string) {
  switch (status) {
    case 'active': return 'bg-success';
    case 'disabled': return 'bg-danger';
    case 'pending': return 'bg-warning';
    case 'canceled': return 'bg-secondary';
    case 'past_due': return 'bg-warning';
    case 'unpaid': return 'bg-danger';
    case 'trialing': return 'bg-info';
    default: return 'bg-secondary';
  }
}

function getSubscriptionStatus() {
  if (props.currentSubscription) {
    if (props.currentSubscription.ends_at) {
      return 'Cancelling';
    }
    return props.currentSubscription.status;
  }
  return 'No subscription';
}

function impersonateUser() {
  if (confirm(`Impersonate ${props.customer.name}?`)) {
    isLoading.value = true;
    router.post(route('admin.customers.impersonate', props.customer.id), {}, {
      onFinish: () => { isLoading.value = false; },
      onError: (errors) => {
        console.error('Impersonation failed:', errors);
        alert('Failed to impersonate user. Please try again.');
      }
    });
  }
}

function sendPasswordReset() {
  if (confirm(`Send password reset link to ${props.customer.email}?`)) {
    isLoading.value = true;
    router.post(route('admin.customers.password-reset', props.customer.id), {}, {
      onFinish: () => { isLoading.value = false; },
      onSuccess: () => {
        alert('Password reset link sent successfully!');
      },
      onError: (errors) => {
        console.error('Password reset failed:', errors);
        alert('Failed to send password reset. Please try again.');
      }
    });
  }
}

function cancelSubscription(immediately = false) {
  const message = immediately 
    ? 'Cancel this subscription immediately? The user will lose access right away.'
    : 'Cancel this subscription at the end of the billing period?';
    
  if (confirm(message)) {
    isLoading.value = true;
    router.post(route('admin.customers.cancel-subscription', props.customer.id), {
      immediately
    }, {
      onFinish: () => { isLoading.value = false; },
      onSuccess: () => {
        alert('Subscription cancelled successfully!');
        router.reload();
      },
      onError: (errors) => {
        console.error('Cancellation failed:', errors);
        alert('Failed to cancel subscription. Please try again.');
      }
    });
  }
}

function openGrantModal() {
  showGrantModal.value = true;
  // Navigate to customers page with modal open
  router.get(route('admin.customers.index'), { modal: 'grant', user_id: props.customer.id });
}

function toggleUserStatus() {
  const newStatus = props.customer.status === 'active' ? 'disabled' : 'active';
  const action = newStatus === 'disabled' ? 'disable' : 'enable';
  
  if (confirm(`Are you sure you want to ${action} ${props.customer.name}?`)) {
    isLoading.value = true;
    router.put(route('admin.customers.update', props.customer.id), {
      status: newStatus
    }, {
      onFinish: () => { isLoading.value = false; },
      onSuccess: () => {
        router.reload();
      },
      onError: (errors) => {
        console.error('Status update failed:', errors);
        alert('Failed to update user status. Please try again.');
      }
    });
  }
}

const stripeMode = computed(() => {
  const stripeKey = (window as any).stripeKey || '';
  return stripeKey.includes('pk_test_') ? 'test' : 'live';
});

function formatPropertyKey(key: string): string {
  // Convert snake_case to Title Case
  return key
    .split('_')
    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
    .join(' ');
}
</script>

<template>
  <AdminLayout>
    <Head :title="`Customer: ${customer.name}`" />
    
    <!-- Loading Overlay -->
    <div v-if="isLoading" class="position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center" style="background: rgba(0,0,0,0.5); z-index: 9999;">
      <div class="spinner-border text-light" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
    </div>
    
    <div class="container-fluid p-4">
      <!-- Header -->
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2">
              <li class="breadcrumb-item">
                <Link :href="route('admin.dashboard')">Admin</Link>
              </li>
              <li class="breadcrumb-item">
                <Link :href="route('admin.customers.index')">Customers</Link>
              </li>
              <li class="breadcrumb-item active">{{ customer.name }}</li>
            </ol>
          </nav>
          <h1 class="h2 mb-0">{{ customer.name }}</h1>
        </div>
        <div class="d-flex gap-2">
          <button class="btn btn-warning" @click="impersonateUser">
            <i class="bi bi-person-badge me-2"></i>Impersonate
          </button>
          <Link :href="route('admin.customers.index')" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back to Customers
          </Link>
        </div>
      </div>

      <!-- Customer Overview Cards -->
      <div class="row g-3 mb-4">
        <div class="col-md-3">
          <div class="card h-100">
            <div class="card-body">
              <h6 class="card-subtitle mb-2 text-muted">Account Status</h6>
              <div class="d-flex align-items-center">
                <span :class="['badge', getStatusBadgeClass(customer.status), 'me-2']">
                  {{ customer.status }}
                </span>
                <div v-if="customer.is_ambassador || customer.is_gifted || customer.admin_override" class="d-flex gap-1">
                  <span v-if="customer.is_ambassador" class="badge bg-primary" style="font-size: 0.7rem;">
                    Ambassador
                  </span>
                  <span v-if="customer.is_gifted" class="badge bg-success" style="font-size: 0.7rem;">
                    Gifted
                  </span>
                  <span v-if="customer.admin_override" class="badge bg-purple" style="font-size: 0.7rem;">
                    Override
                  </span>
                </div>
              </div>
              <div class="mt-2 small text-muted">
                <i class="bi bi-envelope me-1"></i>{{ customer.email }}
              </div>
              <div class="small text-muted">
                <i class="bi bi-calendar me-1"></i>Customer since {{ formatDate(customer.created_at) }}
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card h-100">
            <div class="card-body">
              <h6 class="card-subtitle mb-2 text-muted">Subscription</h6>
              <div class="mb-2">
                <span :class="['badge', getStatusBadgeClass(getSubscriptionStatus())]">
                  {{ getSubscriptionStatus() }}
                </span>
              </div>
              <div v-if="currentSubscription" class="small">
                <div v-if="currentSubscription.current_period_end">
                  <strong>Next billing:</strong> {{ formatDate(currentSubscription.current_period_end) }}
                </div>
                <div v-if="currentSubscription.days_until_renewal !== null">
                  <span v-if="currentSubscription.days_until_renewal > 0" class="text-success">
                    {{ currentSubscription.days_until_renewal }} days remaining
                  </span>
                  <span v-else-if="currentSubscription.days_until_renewal < 0" class="text-danger">
                    Expired {{ Math.abs(currentSubscription.days_until_renewal) }} days ago
                  </span>
                  <span v-else class="text-warning">
                    Expires today
                  </span>
                </div>
                <div v-if="currentSubscription.is_manual" class="text-info mt-1">
                  <i class="bi bi-info-circle me-1"></i>Manual override (no billing)
                </div>
              </div>
              <div v-else class="small text-muted">
                No active subscription
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card h-100">
            <div class="card-body">
              <h6 class="card-subtitle mb-2 text-muted">Payment Method</h6>
              <div v-if="paymentMethods.length > 0">
                <div v-for="method in paymentMethods" :key="method.id" class="small">
                  <i class="bi bi-credit-card me-1"></i>
                  {{ method.brand }} ending in {{ method.last4 }}
                  <span v-if="method.is_default" class="badge bg-primary ms-1" style="font-size: 0.65rem;">Default</span>
                  <div class="text-muted">Expires {{ method.exp_month }}/{{ method.exp_year }}</div>
                </div>
              </div>
              <div v-else class="small text-danger">
                <i class="bi bi-exclamation-circle me-1"></i>No payment method
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card h-100">
            <div class="card-body">
              <h6 class="card-subtitle mb-2 text-muted">Lifetime Value</h6>
              <div class="h4 mb-0">{{ formatCurrency(stats.total_spent * 100) }}</div>
              <div class="small text-muted">
                {{ stats.subscription_count }} subscriptions
              </div>
              <div class="small text-muted">
                {{ stats.days_as_customer }} days as customer
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Tabs -->
      <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
          <button 
            class="nav-link"
            :class="{ active: activeTab === 'overview' }"
            @click="activeTab = 'overview'"
          >
            <i class="bi bi-speedometer2 me-2"></i>Overview
          </button>
        </li>
        <li class="nav-item">
          <button 
            class="nav-link"
            :class="{ active: activeTab === 'subscriptions' }"
            @click="activeTab = 'subscriptions'"
          >
            <i class="bi bi-credit-card me-2"></i>Subscriptions
          </button>
        </li>
        <li class="nav-item">
          <button 
            class="nav-link"
            :class="{ active: activeTab === 'invoices' }"
            @click="activeTab = 'invoices'"
          >
            <i class="bi bi-receipt me-2"></i>Invoices
          </button>
        </li>
        <li class="nav-item">
          <button 
            class="nav-link"
            :class="{ active: activeTab === 'activity' }"
            @click="activeTab = 'activity'"
          >
            <i class="bi bi-activity me-2"></i>Activity Log
          </button>
        </li>
      </ul>

      <!-- Tab Content -->
      <div class="tab-content">
        <!-- Overview Tab -->
        <div v-if="activeTab === 'overview'" class="tab-pane active">
          <div class="row g-4">
            <!-- Quick Actions -->
            <div class="col-md-6">
              <div class="card">
                <div class="card-header">
                  <h5 class="card-title mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                  <div class="d-grid gap-2">
                    <button 
                      class="btn btn-outline-primary text-start"
                      @click="openGrantModal"
                    >
                      <i class="bi bi-gift me-2"></i>Grant/Update Subscription
                    </button>
                    <button 
                      class="btn text-start"
                      :class="customer.status === 'active' ? 'btn-outline-danger' : 'btn-outline-success'"
                      @click="toggleUserStatus"
                    >
                      <i :class="['bi me-2', customer.status === 'active' ? 'bi-x-circle' : 'bi-check-circle']"></i>
                      {{ customer.status === 'active' ? 'Disable User' : 'Enable User' }}
                    </button>
                    <button class="btn btn-outline-warning text-start" @click="impersonateUser">
                      <i class="bi bi-person-badge me-2"></i>Impersonate User
                    </button>
                    <button class="btn btn-outline-primary text-start" @click="sendPasswordReset">
                      <i class="bi bi-key me-2"></i>Send Password Reset
                    </button>
                    <a 
                      v-if="customer.stripe_id"
                      :href="`https://dashboard.stripe.com/${stripeMode}/customers/${customer.stripe_id}`"
                      target="_blank"
                      class="btn btn-outline-primary text-start"
                    >
                      <i class="bi bi-stripe me-2"></i>View in Stripe
                      <i class="bi bi-box-arrow-up-right ms-1 small"></i>
                    </a>
                    
                    <!-- Subscription Actions -->
                    <div v-if="currentSubscription && !currentSubscription.ends_at && !currentSubscription.is_manual" class="mt-2">
                      <hr>
                      <p class="text-muted small mb-2">Subscription Actions:</p>
                      <button 
                        class="btn btn-outline-warning btn-sm text-start w-100 mb-2" 
                        @click="cancelSubscription(false)"
                      >
                        <i class="bi bi-x-circle me-2"></i>Cancel at Period End
                      </button>
                      <button 
                        class="btn btn-outline-danger btn-sm text-start w-100" 
                        @click="cancelSubscription(true)"
                      >
                        <i class="bi bi-x-circle-fill me-2"></i>Cancel Immediately
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Current Plan Details -->
            <div class="col-md-6">
              <div class="card">
                <div class="card-header">
                  <h5 class="card-title mb-0">Current Plan Details</h5>
                </div>
                <div class="card-body">
                  <div v-if="currentSubscription && subscriptionHistory.length > 0">
                    <dl class="row mb-0">
                      <dt class="col-sm-4">Plan:</dt>
                      <dd class="col-sm-8">
                        {{ subscriptionHistory[0].tier || 'Custom' }} 
                        {{ subscriptionHistory[0].interval || '' }}
                      </dd>
                      <dt class="col-sm-4">Status:</dt>
                      <dd class="col-sm-8">
                        <span :class="['badge', getStatusBadgeClass(currentSubscription.status)]">
                          {{ currentSubscription.status }}
                        </span>
                      </dd>
                      <dt class="col-sm-4">Started:</dt>
                      <dd class="col-sm-8">{{ formatDate(subscriptionHistory[0].current_period_start) }}</dd>
                      <dt class="col-sm-4">Renews:</dt>
                      <dd class="col-sm-8">
                        {{ currentSubscription.ends_at ? 'Cancelled' : formatDate(currentSubscription.current_period_end) }}
                      </dd>
                    </dl>
                  </div>
                  <div v-else class="text-muted">
                    No active subscription
                  </div>
                </div>
              </div>
            </div>

            <!-- Override Details -->
            <div v-if="customer.admin_override" class="col-12">
              <div class="card border-purple">
                <div class="card-header bg-purple text-white">
                  <h5 class="card-title mb-0">
                    <i class="bi bi-shield-check me-2"></i>Admin Override Active
                  </h5>
                </div>
                <div class="card-body">
                  <dl class="row mb-0">
                    <dt class="col-sm-2">Override Tier:</dt>
                    <dd class="col-sm-10">{{ customer.override_tier || 'Not specified' }}</dd>
                    <dt class="col-sm-2">Expires:</dt>
                    <dd class="col-sm-10">{{ customer.override_expiry ? formatDate(customer.override_expiry) : 'Never' }}</dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Subscriptions Tab -->
        <div v-if="activeTab === 'subscriptions'" class="tab-pane active">
          <div class="card">
            <div class="card-header">
              <h5 class="card-title mb-0">Subscription History</h5>
            </div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-hover mb-0">
                  <thead>
                    <tr>
                      <th>Status</th>
                      <th>Plan</th>
                      <th>Period</th>
                      <th>Start Date</th>
                      <th>End Date</th>
                      <th>Type</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="sub in subscriptionHistory" :key="sub.id">
                      <td>
                        <span :class="['badge', getStatusBadgeClass(sub.status)]">
                          {{ sub.status }}
                        </span>
                      </td>
                      <td>{{ sub.tier || 'Custom' }}</td>
                      <td>{{ sub.interval || 'N/A' }}</td>
                      <td>{{ formatDate(sub.current_period_start || sub.created_at) }}</td>
                      <td>
                        <span v-if="sub.ends_at">{{ formatDate(sub.ends_at) }}</span>
                        <span v-else-if="sub.current_period_end">{{ formatDate(sub.current_period_end) }}</span>
                        <span v-else class="text-muted">Active</span>
                      </td>
                      <td>
                        <span v-if="sub.is_manual" class="badge bg-info">Manual</span>
                        <span v-else class="badge bg-secondary">Stripe</span>
                      </td>
                    </tr>
                  </tbody>
                </table>
                <div v-if="subscriptionHistory.length === 0" class="text-center p-4 text-muted">
                  No subscription history
                </div>
              </div>
            </div>
          </div>

          <!-- Discount Usage -->
          <div v-if="discountUsage.length > 0" class="card mt-4">
            <div class="card-header">
              <h5 class="card-title mb-0">Discount Code Usage</h5>
            </div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-hover mb-0">
                  <thead>
                    <tr>
                      <th>Code</th>
                      <th>Amount</th>
                      <th>Type</th>
                      <th>Used On</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(discount, index) in discountUsage" :key="index">
                      <td><code>{{ discount.code }}</code></td>
                      <td>
                        <span v-if="discount.type === 'percentage'">{{ discount.amount }}%</span>
                        <span v-else>{{ formatCurrency(discount.amount * 100) }}</span>
                      </td>
                      <td>{{ discount.type }}</td>
                      <td>{{ formatDate(discount.created_at) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- Invoices Tab -->
        <div v-if="activeTab === 'invoices'" class="tab-pane active">
          <div class="card">
            <div class="card-header">
              <h5 class="card-title mb-0">Invoice History</h5>
            </div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-hover mb-0">
                  <thead>
                    <tr>
                      <th>Number</th>
                      <th>Amount</th>
                      <th>Status</th>
                      <th>Date</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="invoice in invoices" :key="invoice.id">
                      <td>{{ invoice.number || invoice.id }}</td>
                      <td>{{ formatCurrency(invoice.total) }}</td>
                      <td>
                        <span :class="['badge', invoice.status === 'paid' ? 'bg-success' : 'bg-warning']">
                          {{ invoice.status }}
                        </span>
                      </td>
                      <td>{{ formatDate(invoice.created_at) }}</td>
                      <td>
                        <div class="btn-group btn-group-sm">
                          <a 
                            :href="invoice.hosted_invoice_url" 
                            target="_blank" 
                            class="btn btn-outline-primary"
                          >
                            <i class="bi bi-eye"></i> View
                          </a>
                          <a 
                            :href="invoice.invoice_pdf" 
                            target="_blank" 
                            class="btn btn-outline-secondary"
                          >
                            <i class="bi bi-download"></i> PDF
                          </a>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
                <div v-if="invoices.length === 0" class="text-center p-4 text-muted">
                  No invoices found
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Activity Tab -->
        <div v-if="activeTab === 'activity'" class="tab-pane active">
          <div class="card">
            <div class="card-header">
              <h5 class="card-title mb-0">Activity Log</h5>
            </div>
            <div class="card-body p-0">
              <div class="list-group list-group-flush">
                <div v-for="activity in activities" :key="activity.id" class="list-group-item">
                  <div class="d-flex justify-content-between align-items-start">
                    <div>
                      <h6 class="mb-1">{{ activity.description }}</h6>
                      <div v-if="activity.properties && Object.keys(activity.properties).length > 0" class="small text-muted">
                        <ul class="mb-0 list-unstyled">
                          <li v-for="(value, key) in activity.properties" :key="key" v-if="key !== 'user_agent'">
                            <strong>{{ formatPropertyKey(key) }}:</strong> {{ value }}
                          </li>
                          <li v-if="activity.properties.user_agent" class="mt-1">
                            <strong>User Agent:</strong> {{ activity.properties.user_agent }}
                          </li>
                          <li class="mt-1">
                            <span v-if="activity.causer">
                              <strong>By:</strong> {{ activity.causer.name }} ({{ activity.causer.email }})
                            </span>
                            <span v-else><strong>By:</strong> System</span>
                          </li>
                          <li>
                            <strong>Date:</strong> {{ formatDate(activity.created_at) }}
                          </li>
                        </ul>
                      </div>
                      <div v-else class="small text-muted mt-1">
                        <span v-if="activity.causer">
                          by {{ activity.causer.name }} ({{ activity.causer.email }})
                        </span>
                        <span v-else>System</span>
                        • {{ formatDate(activity.created_at) }}
                      </div>
                    </div>
                  </div>
                </div>
                <div v-if="activities.length === 0" class="text-center p-4 text-muted">
                  No activity recorded
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<style scoped>
.bg-purple {
  background-color: #6f42c1 !important;
  color: white;
}

.border-purple {
  border-color: #6f42c1 !important;
}

.nav-tabs .nav-link {
  color: #6c757d;
}

.nav-tabs .nav-link.active {
  font-weight: 600;
}

.badge {
  font-weight: 500;
}
</style>
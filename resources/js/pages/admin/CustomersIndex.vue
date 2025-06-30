<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import { reactive } from 'vue';

const props = defineProps<{ customers: Array<any> }>();
const page = usePage();
const stripePrices = page.props.stripePrices || {};

const editableSubs = reactive(
  props.customers.reduce((acc, customer) => {
    const sub = customer.subscriptions && customer.subscriptions.length > 0
      ? customer.subscriptions[0]
      : { stripe_status: '', price: '', trial_ends_at: null };
    acc[customer.id] = {
      subscription_price: sub.price || '',
      subscription_status: sub.stripe_status || '',
      trial_days: '',
      trial_ends_at: sub.trial_ends_at,
    };
    return acc;
  }, {})
);

function updateSubscription(user) {
  const form = useForm({
    subscription_price: editableSubs[user.id].subscription_price,
    subscription_status: editableSubs[user.id].subscription_status,
    trial_days: editableSubs[user.id].trial_days,
  });

  form.put(route('admin.customers.update', user.id), {
    preserveScroll: true,
    onSuccess: () => {
      editableSubs[user.id].trial_days = '';
    }
  });
}

function impersonateUser(user) {
  router.post(route('admin.customers.impersonate', user.id));
}

function sendPasswordReset(user) {
  if (confirm(`Send password reset link to ${user.email}?`)) {
    router.post(route('admin.customers.password-reset', user.id), {}, {
      preserveScroll: true,
      onSuccess: (page) => {
        // Show success message if available
        if (page.props.flash?.success) {
          alert(page.props.flash.success);
        }
      },
      onError: (errors) => {
        // Show error message if available
        if (page.props.flash?.error) {
          alert(page.props.flash.error);
        } else {
          alert('Failed to send password reset link.');
        }
      }
    });
  }
}
</script>

<template>
  <AdminLayout>
    <Head title="Customers" />
    <div class="container-fluid p-4">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2 mb-0">Customers & Subscriptions</h1>
      </div>
      
      <div class="card">
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-hover align-middle">
              <thead class="table-light">
                <tr>
                  <th>Name</th>
                  <th>Email</th>
                  <th>Current Status</th>
                  <th>Assign Plan</th>
                  <th>Status</th>
                  <th>Trial</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="customer in props.customers" :key="customer.id">
                  <td>
                    <div class="fw-medium">{{ customer.name }}</div>
                    <small class="text-muted">ID: {{ customer.id }}</small>
                  </td>
                  <td>{{ customer.email }}</td>
                  <td>
                    <div v-if="customer.subscriptions.length">
                      <span :class="[
                        'badge',
                        customer.subscriptions[0].stripe_status === 'active' ? 'bg-success' :
                        customer.subscriptions[0].stripe_status === 'canceled' ? 'bg-secondary' :
                        customer.subscriptions[0].stripe_status === 'past_due' ? 'bg-warning' :
                        'bg-danger'
                      ]">
                        {{ customer.subscriptions[0].stripe_status }}
                      </span>
                      <div v-if="customer.subscriptions[0].trial_ends_at" class="small text-warning mt-1">
                        <i class="bi bi-clock me-1"></i>Trial ends: {{ customer.subscriptions[0].trial_ends_at }}
                      </div>
                    </div>
                    <div v-else>
                      <span class="text-muted">No subscription</span>
                    </div>
                  </td>
                  <td>
                    <select v-model="editableSubs[customer.id].subscription_price" class="form-select form-select-sm">
                      <option value="">-- Select Plan --</option>
                      <option v-for="(price, key) in stripePrices" :key="key" :value="price">
                        {{ key.replace('_', ' ').toUpperCase() }}
                      </option>
                    </select>
                  </td>
                  <td>
                    <select v-model="editableSubs[customer.id].subscription_status" class="form-select form-select-sm">
                      <option value="active">Active</option>
                      <option value="canceled">Canceled</option>
                      <option value="past_due">Past Due</option>
                      <option value="unpaid">Unpaid</option>
                    </select>
                  </td>
                  <td>
                    <input
                      v-model="editableSubs[customer.id].trial_days"
                      type="number"
                      min="0"
                      placeholder="Days"
                      class="form-control form-control-sm"
                      style="width: 80px;"
                    />
                  </td>
                  <td>
                    <div class="d-flex gap-1">
                      <button
                        class="btn btn-sm btn-primary"
                        @click="updateSubscription(customer)"
                        title="Save changes"
                      >
                        <i class="bi bi-save"></i> Save
                      </button>
                      <button
                        class="btn btn-sm btn-warning"
                        @click="impersonateUser(customer)"
                        title="Impersonate this user"
                      >
                        <i class="bi bi-person-badge"></i>
                      </button>
                      <button
                        class="btn btn-sm btn-secondary"
                        @click="sendPasswordReset(customer)"
                        title="Send password reset link"
                      >
                        <i class="bi bi-key"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
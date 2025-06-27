<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
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
</script>

<template>
    <AppLayout :breadcrumbs="{ title: 'Customers', href: route('admin.customers.index') }">
  <Head title="Customers" />
  <div class="max-w-5xl mx-auto p-6">
    <h1 class="text-2xl font-bold mb-4">Customers & Subscriptions</h1>
    <table class="w-full mt-4 border">
      <thead>
        <tr>
          <th class="text-left p-2">Name</th>
          <th class="text-left p-2">Email</th>
          <th class="text-left p-2">Current Status</th>
          <th class="text-left p-2">Assign Plan</th>
          <th class="text-left p-2">Status</th>
          <th class="text-left p-2">Trial</th>
          <th class="text-left p-2">Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="customer in props.customers" :key="customer.id">
          <td class="p-2">{{ customer.name }}</td>
          <td class="p-2">{{ customer.email }}</td>
          <td class="p-2">
            <div v-if="customer.subscriptions.length">
              {{ customer.subscriptions[0].stripe_status }}<br>
              <span v-if="customer.subscriptions[0].trial_ends_at" class="text-xs text-yellow-400">
                Trial ends: {{ customer.subscriptions[0].trial_ends_at }}
              </span>
            </div>
            <div v-else>
              <span class="text-gray-400">No subscription</span>
            </div>
          </td>
          <td class="p-2">
            <select v-model="editableSubs[customer.id].subscription_price" class="border rounded px-2 py-1">
              <option value="">-- Select Plan --</option>
              <option v-for="(price, key) in stripePrices" :key="key" :value="price">
                {{ key.replace('_', ' ').toUpperCase() }}
              </option>
            </select>
          </td>
          <td class="p-2">
            <select v-model="editableSubs[customer.id].subscription_status" class="border rounded px-2 py-1">
              <option value="active">Active</option>
              <option value="canceled">Canceled</option>
              <option value="past_due">Past Due</option>
              <option value="unpaid">Unpaid</option>
            </select>
          </td>
          <td class="p-2">
            <input
              v-model="editableSubs[customer.id].trial_days"
              type="number"
              min="0"
              placeholder="Days"
              class="border rounded px-2 py-1 w-20"
            />
          </td>
          <td class="p-2">
            <button
              class="bg-indigo-600 text-white px-3 py-1 rounded"
              @click="updateSubscription(customer)"
            >
              Save
            </button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
  </AppLayout>
</template>
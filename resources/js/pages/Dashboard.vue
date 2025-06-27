<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import BetPickCard from '../components/BetPickCard.vue';
import NewBetPickForm from '../components/NewBetPickForm.vue';
import SubscriptionROIChart from '../components/SubscriptionROIChart.vue';
import SportProfitAndROIChart from '../components/SportProfitAndROIChart.vue';
import BetsUpload from '@/components/BetsUpload.vue';
import { ref, computed } from 'vue';
import axios from 'axios';
import BetsExport from '@/components/BetsExport.vue';
const page = usePage<SharedData>();

const user = page.props.auth.user.data as User;
const bets = page.props.bets || [];
const flash = page.props.flash || {};
const roiData = page.props.roiData || {};
const sportProfitRoiData = page.props.sportProfitRoiData || {};
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

// Pagination logic
const pageSize = 15;
const currentPage = ref(1);
const totalPages = computed(() => Math.ceil(bets.length / pageSize));
const paginatedBets = computed(() =>
    bets.slice((currentPage.value - 1) * pageSize, currentPage.value * pageSize)
);

function goToPage(pageNum: number) {
    if (pageNum >= 1 && pageNum <= totalPages.value) {
        currentPage.value = pageNum;
    }
}

// Admin notification form logic
const notifyTitle = ref('');
const notifyBody = ref('');
const notifyLoading = ref(false);
const notifySuccess = ref('');
const notifyError = ref('');

const sendNotification = async () => {
    notifyLoading.value = true;
    notifySuccess.value = '';
    notifyError.value = '';
    try {
        await axios.post('/admin/notify-all', {
            title: notifyTitle.value,
            body: notifyBody.value,
        });
        notifySuccess.value = 'Notification sent to all users!';
        notifyTitle.value = '';
        notifyBody.value = '';
    } catch (e) {
        notifyError.value = 'Failed to send notification.';
    } finally {
        notifyLoading.value = false;
    }
};

const fetchBets = async () => {
    // You may want to use Inertia visit or axios, depending on your setup
    const response = await axios.get('/api/bets');
    // If using Inertia, you may want to use Inertia.reload() or Inertia.visit()
    // If using axios:
    bets.splice(0, bets.length, ...response.data);
};

// Update bet function (to be called on form submission in BetPickCard)
const updateBet = async (betId, formData) => {
    try {
        await axios.post(`/api/bets/${betId}`, formData);
        alert('Bet updated successfully!');
        fetchBets(); // Call this to refresh the bets array
    } catch (error) {
        console.error('Error updating bet:', error);
        alert('Failed to update bet.');
    }
};

const updateBetInArray = (updatedBet) => {
    const idx = bets.findIndex(b => b.id === updatedBet.id);
    if (idx !== -1) {
        bets[idx] = updatedBet;
    }
};
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Flash Messages -->
            <div v-if="flash.success" class="mb-4 rounded-lg bg-green-100 p-4 text-green-800">
                {{ flash.success }}
            </div>
            <div v-if="flash.error" class="mb-4 rounded-lg bg-red-100 p-4 text-red-800">
                {{ flash.error }}
            </div>

         
              <!-- Admin Notification Form -->
            <div v-if="user.roles[0] && user.roles[0].name == 'admin'" class="mb-8">
                <div class="flex justify-center mb-6">
                    <BetsExport />
                </div>
                <div class="max-w-3xl mx-auto p-6 bg-white dark:bg-gray-800 rounded-lg shadow-md">
                    <h2 class="text-xl font-bold text-white mb-4">Send Notification to All Users</h2>
                    <form @submit.prevent="sendNotification" class="space-y-4">
                        <div>
                            <label class="block text-gray-200 mb-1">Title</label>
                            <input v-model="notifyTitle" type="text" class="w-full rounded border-gray-600 bg-gray-900 text-white px-3 py-2" required />
                        </div>
                        <div>
                            <label class="block text-gray-200 mb-1">Body</label>
                            <textarea v-model="notifyBody" class="w-full rounded border-gray-600 bg-gray-900 text-white px-3 py-2" rows="3" required></textarea>
                        </div>
                        <button
                            type="submit"
                            class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded font-semibold transition"
                            :disabled="notifyLoading"
                        >
                            <span v-if="notifyLoading">Sending...</span>
                            <span v-else>Send Notification</span>
                        </button>
                        <div v-if="notifySuccess" class="text-green-400 mt-2">{{ notifySuccess }}</div>
                        <div v-if="notifyError" class="text-red-400 mt-2">{{ notifyError }}</div>
                    </form>
                </div>
            </div>
            <!-- New Bet Pick Form (Admin Only) -->
            <div v-if="user.roles[0] && user.roles[0].name == 'admin'" class="mt-6">
                <BetsUpload />
            </div>
            <div v-if="user.roles[0] && user.roles[0].name == 'admin'" class="mt-6">
                <NewBetPickForm />
            </div>
             
            <SubscriptionROIChart :roi-data="roiData" v-if="user.roles[0] && user.roles[0].name == 'admin'"/>
            <section class="py-16 bg-gray-900">
                <div class="container mx-auto px-4 text-center">
                    <h2 class="text-3xl font-bold text-white">Profit & ROI by Sport</h2>
                    <p class="mt-4 text-gray-400">See profit and ROI for each sport.</p>
                    <div class="mt-8">
                        <SportProfitAndROIChart :data="sportProfitRoiData" />
                    </div>
                </div>
            </section>
            <!-- Bet Pick Cards with Pagination -->
            <div v-if="user.roles[0] && user.roles[0].name == 'admin'">
                <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                    <BetPickCard
                        v-for="bet in paginatedBets"
                        :key="bet.id"
                        :bet="bet"
                        @bet-updated="updateBetInArray"
                    />
                </div>
                <!-- Pagination Controls -->
                <div class="flex justify-center items-center mt-6 gap-2" v-if="totalPages > 1">
                    <button
                        class="px-3 py-1 rounded bg-gray-700 text-white hover:bg-indigo-600"
                        :disabled="currentPage === 1"
                        @click="goToPage(currentPage - 1)"
                    >
                        Prev
                    </button>
                    <span class="text-gray-200 mx-2">Page {{ currentPage }} of {{ totalPages }}</span>
                    <button
                        class="px-3 py-1 rounded bg-gray-700 text-white hover:bg-indigo-600"
                        :disabled="currentPage === totalPages"
                        @click="goToPage(currentPage + 1)"
                    >
                        Next
                    </button>
                </div>
            </div>

            <!-- Subscription Section -->
            <div v-if="user.subscriptions.length > 0" class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border md:min-h-min">
                <!-- Additional content can go here -->
            </div>
            <div v-else-if="user.roles[0] && user.roles[0].name !== 'admin'" class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border md:min-h-min">
                You aren't subscribed to any subscriptions
            </div>
        </div>
    </AppLayout>
</template>

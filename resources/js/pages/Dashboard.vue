<script setup lang="ts">
import BetsExport from '@/components/BetsExport.vue';
import BetsUpload from '@/components/BetsUpload.vue';
import CustomerLayout from '@/layouts/CustomerLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, ref } from 'vue';
import BetPickCard from '../components/BetPickCard.vue';
import NewBetPickForm from '../components/NewBetPickForm.vue';
import SportProfitAndROIChart from '../components/SportProfitAndROIChart.vue';
import SubscriptionROIChart from '../components/SubscriptionROIChart.vue';
const page = usePage<SharedData>();

const user = page.props.auth.user.data as User;
const bets = page.props.bets || [];
const flash = page.props.flash || {};
const roiData = page.props.roiData || {};
const sportProfitRoiData = page.props.sportProfitRoiData || {};

// Pagination logic
const pageSize = 15;
const currentPage = ref(1);
const totalPages = computed(() => Math.ceil(bets.length / pageSize));
const paginatedBets = computed(() => bets.slice((currentPage.value - 1) * pageSize, currentPage.value * pageSize));

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
        // console.error('Error updating bet:', error);
        alert('Failed to update bet.');
    }
};

const updateBetInArray = (updatedBet) => {
    const idx = bets.findIndex((b) => b.id === updatedBet.id);
    if (idx !== -1) {
        bets[idx] = updatedBet;
    }
};
</script>

<template>
    <Head title="Dashboard" />

    <CustomerLayout>
        <div class="container-fluid py-4">
            <!-- Flash Messages -->
            <div v-if="flash.success" class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                {{ flash.success }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <div v-if="flash.error" class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                {{ flash.error }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>

            <!-- Admin Notification Form -->
            <div v-if="user.roles[0] && user.roles[0].name == 'admin'" class="mb-5">
                <div class="d-flex justify-content-center mb-4">
                    <BetsExport />
                </div>
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="card bg-dark text-white">
                            <div class="card-header">
                                <h5 class="mb-0">Send Notification to All Users</h5>
                            </div>
                            <div class="card-body">
                                <form @submit.prevent="sendNotification">
                                    <div class="mb-3">
                                        <label class="form-label">Title</label>
                                        <input
                                            v-model="notifyTitle"
                                            type="text"
                                            class="form-control bg-secondary text-white border-secondary"
                                            required
                                        />
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Body</label>
                                        <textarea
                                            v-model="notifyBody"
                                            class="form-control bg-secondary text-white border-secondary"
                                            rows="3"
                                            required
                                        ></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary" :disabled="notifyLoading">
                                        <span v-if="notifyLoading">
                                            <span class="spinner-border spinner-border-sm me-2"></span>
                                            Sending...
                                        </span>
                                        <span v-else>Send Notification</span>
                                    </button>
                                    <div v-if="notifySuccess" class="alert alert-success mt-3">{{ notifySuccess }}</div>
                                    <div v-if="notifyError" class="alert alert-danger mt-3">{{ notifyError }}</div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- New Bet Pick Form (Admin Only) -->
            <div v-if="user.roles[0] && user.roles[0].name == 'admin'" class="mb-4">
                <BetsUpload />
            </div>
            <div v-if="user.roles[0] && user.roles[0].name == 'admin'" class="mb-4">
                <NewBetPickForm />
            </div>

            <SubscriptionROIChart :roi-data="roiData" v-if="user.roles[0] && user.roles[0].name == 'admin'" class="mb-5" />
            <section class="bg-dark text-white rounded p-5 mb-5">
                <div class="text-center">
                    <h2 class="h3 mb-3">Profit & ROI by Sport</h2>
                    <p class="text-muted mb-4">See profit and ROI for each sport.</p>
                    <SportProfitAndROIChart :data="sportProfitRoiData" />
                </div>
            </section>
            <!-- Bet Pick Cards with Pagination -->
            <div v-if="user.roles[0] && user.roles[0].name == 'admin'">
                <div class="row g-4">
                    <div class="col-md-6 col-lg-4" v-for="bet in paginatedBets" :key="bet.id">
                        <BetPickCard :bet="bet" @bet-updated="updateBetInArray" />
                    </div>
                </div>
                <!-- Pagination Controls -->
                <nav v-if="totalPages > 1" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <li class="page-item" :class="{ disabled: currentPage === 1 }">
                            <button class="page-link" @click="goToPage(currentPage - 1)" :disabled="currentPage === 1">Previous</button>
                        </li>
                        <li class="page-item active">
                            <span class="page-link">Page {{ currentPage }} of {{ totalPages }}</span>
                        </li>
                        <li class="page-item" :class="{ disabled: currentPage === totalPages }">
                            <button class="page-link" @click="goToPage(currentPage + 1)" :disabled="currentPage === totalPages">Next</button>
                        </li>
                    </ul>
                </nav>
            </div>

            <!-- Subscription Section -->
            <div v-if="user.subscriptions.length > 0" class="card bg-light" style="min-height: 50vh">
                <div class="card-body">
                    <!-- Additional content can go here -->
                </div>
            </div>
            <div v-else-if="user.roles[0] && user.roles[0].name !== 'admin'" class="card bg-light" style="min-height: 50vh">
                <div class="card-body d-flex align-items-center justify-content-center">
                    <div class="text-center">
                        <h5 class="text-muted">You aren't subscribed to any subscriptions</h5>
                        <a href="/pricing" class="btn btn-primary mt-3">View Plans</a>
                    </div>
                </div>
            </div>
        </div>
    </CustomerLayout>
</template>

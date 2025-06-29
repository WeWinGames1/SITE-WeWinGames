<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Input } from '@/components/ui/input';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { 
    ShieldCheckIcon,
    UserIcon,
    LockClosedIcon,
    ChartBarIcon,
    UsersIcon,
    CurrencyDollarIcon,
    DocumentTextIcon
} from '@heroicons/vue/24/outline';

defineProps<{
    canResetPassword?: boolean;
    status?: string;
}>();

const showPassword = ref(false);

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('admin.login'), {
        onFinish: () => {
            form.reset('password');
        },
    });
};

// Animation classes for the floating icons
const floatingIcons = [
    { icon: ChartBarIcon, class: 'top-10 left-10', delay: '0s' },
    { icon: UsersIcon, class: 'top-20 right-20', delay: '1s' },
    { icon: CurrencyDollarIcon, class: 'bottom-20 left-20', delay: '2s' },
    { icon: DocumentTextIcon, class: 'bottom-10 right-10', delay: '3s' },
];
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-gray-900 via-blue-900 to-gray-900 flex items-center justify-center relative overflow-hidden">
        <Head title="Admin Login" />

        <!-- Animated Background Elements -->
        <div class="absolute inset-0">
            <!-- Grid Pattern -->
            <div class="absolute inset-0 bg-grid-white/[0.02] bg-[size:50px_50px]"></div>
            
            <!-- Floating Icons -->
            <div v-for="(item, index) in floatingIcons" :key="index" 
                :class="['absolute opacity-10 text-white', item.class]"
                :style="`animation-delay: ${item.delay}`">
                <component :is="item.icon" class="h-20 w-20 animate-float" />
            </div>
            
            <!-- Gradient Overlay -->
            <div class="absolute inset-0 bg-gradient-to-t from-gray-900/50 to-transparent"></div>
        </div>

        <!-- Login Card -->
        <div class="relative z-10 w-full max-w-md mx-auto p-6">
            <div class="bg-gray-800/50 backdrop-blur-xl rounded-2xl shadow-2xl border border-gray-700/50 overflow-hidden">
                <!-- Header -->
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 p-8 text-center">
                    <div class="inline-flex items-center justify-center w-20 h-20 bg-white/10 backdrop-blur rounded-full mb-4">
                        <ShieldCheckIcon class="h-10 w-10 text-white" />
                    </div>
                    <h1 class="text-3xl font-bold text-white mb-2">Admin Portal</h1>
                    <p class="text-blue-100">WeWinGames Management System</p>
                </div>

                <!-- Login Form -->
                <div class="p-8">
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Email Input -->
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-300 mb-2">
                                Administrator Email
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <UserIcon class="h-5 w-5 text-gray-500" />
                                </div>
                                <Input
                                    id="email"
                                    type="email"
                                    v-model="form.email"
                                    class="pl-10 w-full bg-gray-700/50 border-gray-600 text-white placeholder-gray-400 focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="admin@example.com"
                                    required
                                    autofocus
                                    autocomplete="username"
                                />
                            </div>
                            <InputError class="mt-2" :message="form.errors.email" />
                        </div>

                        <!-- Password Input -->
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-300 mb-2">
                                Password
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <LockClosedIcon class="h-5 w-5 text-gray-500" />
                                </div>
                                <Input
                                    id="password"
                                    :type="showPassword ? 'text' : 'password'"
                                    v-model="form.password"
                                    class="pl-10 pr-10 w-full bg-gray-700/50 border-gray-600 text-white placeholder-gray-400 focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="••••••••"
                                    required
                                    autocomplete="current-password"
                                />
                                <button
                                    type="button"
                                    @click="showPassword = !showPassword"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-300"
                                >
                                    <svg v-if="!showPassword" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <svg v-else class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                    </svg>
                                </button>
                            </div>
                            <InputError class="mt-2" :message="form.errors.password" />
                        </div>

                        <!-- Remember Me -->
                        <div class="flex items-center justify-between">
                            <label class="flex items-center">
                                <input
                                    type="checkbox"
                                    v-model="form.remember"
                                    class="rounded bg-gray-700 border-gray-600 text-blue-600 focus:ring-blue-500"
                                />
                                <span class="ml-2 text-sm text-gray-300">Remember me</span>
                            </label>
                        </div>

                        <!-- Status Message -->
                        <div v-if="status" class="bg-green-500/10 border border-green-500/20 rounded-lg p-3 text-sm text-green-400">
                            {{ status }}
                        </div>

                        <!-- Submit Button -->
                        <Button
                            class="w-full justify-center py-3 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800"
                            :class="{ 'opacity-25': form.processing }"
                            :disabled="form.processing"
                        >
                            <span v-if="!form.processing">Access Admin Portal</span>
                            <span v-else class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Authenticating...
                            </span>
                        </Button>
                    </form>
                </div>

                <!-- Footer -->
                <div class="bg-gray-900/50 px-8 py-4 text-center">
                    <p class="text-xs text-gray-400">
                        Authorized personnel only. All activities are logged and monitored.
                    </p>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="mt-8 grid grid-cols-4 gap-4">
                <div class="text-center">
                    <div class="text-2xl font-bold text-white">24/7</div>
                    <div class="text-xs text-gray-400">Uptime</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-white">1.2K</div>
                    <div class="text-xs text-gray-400">Users</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-white">$45K</div>
                    <div class="text-xs text-gray-400">MRR</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-white">98%</div>
                    <div class="text-xs text-gray-400">Satisfaction</div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes float {
    0%, 100% {
        transform: translateY(0) rotate(0deg);
        opacity: 0.1;
    }
    50% {
        transform: translateY(-20px) rotate(10deg);
        opacity: 0.2;
    }
}

.animate-float {
    animation: float 6s ease-in-out infinite;
}

.bg-grid-white\/\[0\.02\] {
    background-image: 
        linear-gradient(to right, rgba(255, 255, 255, 0.02) 1px, transparent 1px),
        linear-gradient(to bottom, rgba(255, 255, 255, 0.02) 1px, transparent 1px);
}
</style>
<script setup lang="ts">
import { ref } from 'vue';
import WelcomeLayout from '@/layouts/WelcomeLayout.vue';
import { router, Head } from '@inertiajs/vue3';

const form = ref({
    first_name: '',
    last_name: '',
    phone: '',
    email: '',
    about: '',
    position: '',
    // resume: null, // If you want to handle file uploads
});

const submitForm = () => {
    router.post('/careers/apply', form.value, {
        onSuccess: () => alert('Application submitted!'),
        onError: () => alert('Submission failed. Please check your input.'),
    });
};
</script>

<template>
    
    <WelcomeLayout>
        <Head title="Careers">
            <!-- <link rel="stylesheet" href="https://rsms.me/inter/inter.css" /> -->
        </Head>
        <div class="min-h-screen bg-gradient-to-b from-indigo-900 via-gray-900 to-black text-gray-200">
            <div class="container mx-auto px-4 py-16 max-w-3xl">
                <h1 class="text-4xl font-bold mb-8 text-white">Careers Jobs</h1>

                <h2 class="text-2xl font-semibold mt-8 mb-4 text-white">Are you looking for a position with</h2>
                <ul class="list-disc list-inside mb-6 space-y-2">
                    <li>Work 10 to 30 hours a week</li>
                    <li>Flexible hours</li>
                    <li>Work in friendly social sports bars</li>
                    <li>Make friends with your co-workers</li>
                    <li>Be part of a fast growing industry with plenty of opportunities for travel and promotion</li>
                    <li>Represent well known brands in the sports betting industry</li>
                </ul>

                <h2 class="text-2xl font-semibold mt-8 mb-4 text-white">Join Us</h2>
                <p class="mb-4">
                    Welcome to <span class="font-bold text-indigo-400">We Win Games</span>, a leading marketing business in the growing world of sports-betting. We sell sports-betting and casino apps in 10 States across the country. Your first position with us will be signing up people to leading sports and casino apps. This will most likely be in one of our 150 sports and social bars or maybe at a specialised event. You will be excited to present our product to the public and assist them through the app download and sign-up process.
                </p>
                <p class="mb-4">
                   We sell sports-betting and casino apps in 10 States across the country. Your first position with us will be signing up people to leading sports and casino apps. This will most likely be in one of our 150 sports and social bars or maybe at a specialised event. You will be excited to present our product to the public and assist them through the app download and sign-up process.

You’ll be part of a company of young, energetic, ambitious and friendly staff who will help you through your first two weeks of training. Then you will have everything you need so you can master your craft and unleash your potential.

                </p>

                <h2 class="text-2xl font-semibold mt-8 mb-4 text-white">States</h2>
                <p class="mb-4">
                   We are in the following States and always looking for hard working entrepreneurial sales staff.  Please let us know below what States you are interested in:
                </p>
                <ul class="list-disc list-inside mb-4 text-yellow-400 font-bold">
                    <li>Arizona - Phoenix</li>
                    <li>Colorado - Denver</li>
                    <li>Indiana - Indianapolis</li>
                    <li>Michigan - Detroit</li>
                    <li>Massachusetts - Boston</li>
                    <li>Pennsylvania - Philadelphia and Pittsburgh</li>
                    <li>New Jersey Shore and Hoboken</li>
                    <li>North Carolina - Charlotte</li>
                    <li>Texas - Austin & Houston</li>
                    <li>Florida - Tampa/St Pete</li>
                </ul>

                <h2 class="text-2xl font-semibold mt-8 mb-4 text-white">Make Great Money!</h2>
                <p class="mb-4">
                    <span class="font-bold text-yellow-400">TOP EARNERS MAKE over $1000 PER 20 HOUR WEEK.</span> The more money you make the happier everyone is. That’s why we are constantly adding new products and venues for you to sell in. We don’t hire anyone. This is a sales job and you have to sell us on why you would be great at this position. We’re looking for people to grow with and are laser focused on being the top dollar earner that we’re looking for.
                </p>

                <h2 class="text-2xl font-semibold mt-8 mb-4 text-white">Our Opportunity</h2>
                <p class="mb-4">
                    We link you to your future career path. We have grown to almost 100 staff in 3 years which creates plenty of growth opportunities for you in both our physical and digital business. This equally applies to the new online sports and casino betting industry. If you prove yourself, the sky is the limit!
                </p>
                <div class="flex flex-wrap gap-4 mb-8">
                    <span class="bg-indigo-700 text-white px-4 py-2 rounded">Apply</span>
                    <span class="bg-indigo-700 text-white px-4 py-2 rounded">Interview</span>
                    <span class="bg-indigo-700 text-white px-4 py-2 rounded">Training</span>
                    <span class="bg-indigo-700 text-white px-4 py-2 rounded">Lift-Off</span>
                </div>

                <h2 class="text-2xl font-semibold mt-8 mb-4 text-white">Submit Your Resume</h2>
                <form class="bg-gray-800 rounded-lg p-6 space-y-4" @submit.prevent="submitForm">
                    <div>
                        <label class="block text-gray-300 mb-1">First Name</label>
                        <input v-model="form.first_name" type="text" class="w-full px-3 py-2 rounded bg-gray-900 text-white" required />
                    </div>
                    <div>
                        <label class="block text-gray-300 mb-1">Last Name</label>
                        <input v-model="form.last_name" type="text" class="w-full px-3 py-2 rounded bg-gray-900 text-white" required />
                    </div>
                    <div>
                        <label class="block text-gray-300 mb-1">Phone*</label>
                        <input v-model="form.phone" type="text" class="w-full px-3 py-2 rounded bg-gray-900 text-white" required />
                    </div>
                    <div>
                        <label class="block text-gray-300 mb-1">Email</label>
                        <input v-model="form.email" type="email" class="w-full px-3 py-2 rounded bg-gray-900 text-white" />
                    </div>
                    <div>
                        <label class="block text-gray-300 mb-1">Tell Us About Yourself *</label>
                        <textarea v-model="form.about" class="w-full px-3 py-2 rounded bg-gray-900 text-white" required></textarea>
                    </div>
                    <div>
                        <label class="block text-gray-300 mb-1">What Are You Applying For?</label>
                        <input v-model="form.position" type="text" class="w-full px-3 py-2 rounded bg-gray-900 text-white" />
                    </div>
                    <!-- Resume upload can be added here if needed -->
                    <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">
                        SUBMIT
                    </button>
                </form>

                <div class="text-xs text-gray-400 mt-8">
                    DISCLAIMER: This site is 100% for entertainment purposes only and does not involve real money betting. Gambling can be addictive, please play responsibly. If you or someone you know has a gambling problem and wants help, call 1-800 GAMBLER in the U.S. This service is intended for adult users 21+ only.
                </div>
            </div>
        </div>
    </WelcomeLayout>
</template>
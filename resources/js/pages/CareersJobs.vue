<script setup lang="ts">
import { ref } from 'vue';
import WelcomeLayout from '@/layouts/WelcomeLayout.vue';
import { router, Head } from '@inertiajs/vue3';

interface Props {
    resumeCategoryId: number | null;
}

const props = defineProps<Props>();

const form = ref({
    first_name: '',
    last_name: '',
    phone: '',
    email: '',
    position: '',
    about: '',
});

const submitForm = () => {
    if (!props.resumeCategoryId) {
        alert('Sorry, the resume submission system is not available at the moment. Please try again later.');
        return;
    }
    
    // Create the content for the support ticket
    const ticketContent = `
Job Application Details:
-----------------------
Name: ${form.value.first_name} ${form.value.last_name}
Phone: ${form.value.phone}
Email: ${form.value.email}
Position Applied For: ${form.value.position}

About the Applicant:
${form.value.about}
    `.trim();

    // Submit as a support ticket
    router.post('/support', {
        category_id: props.resumeCategoryId,
        subject: `Job Application - ${form.value.position || 'General'}`,
        content: ticketContent,
        priority: 'medium',
        first_name: form.value.first_name,
        last_name: form.value.last_name,
        email: form.value.email,
    }, {
        onSuccess: () => {
            alert('Your application has been submitted successfully! We will review it and get back to you soon.');
            form.value = {
                first_name: '',
                last_name: '',
                phone: '',
                email: '',
                position: '',
                about: '',
            };
        },
        onError: () => alert('Submission failed. Please check your input.'),
    });
};
</script>

<template>
    <WelcomeLayout>
        <Head title="Careers" />
        
        <div class="py-5" style="background: linear-gradient(180deg, #1a2332 0%, #0a1628 100%); min-height: 100vh;">
            <article class="container-fluid px-4 px-lg-5" style="max-width: 1200px;">
                <!-- Article Header -->
                <header class="mb-5">
                    <h1 class="display-4 fw-bold text-white mb-4">
                        Careers
                    </h1>
                </header>

                <!-- Article Content -->
                <div class="article-content mb-5">
                    <h2>Are you looking for a position with</h2>
                    <ul>
                        <li>Work 10 to 30 hours a week</li>
                        <li>Flexible hours</li>
                        <li>Work in friendly social sports bars</li>
                        <li>Make friends with your co-workers</li>
                        <li>Be part of a fast growing industry with plenty of opportunities for travel and promotion</li>
                        <li>Represent well known brands in the sports betting industry</li>
                    </ul>

                    <h2>Join Us</h2>
                    <p>
                        Welcome to <strong>We Win Games</strong>, a leading marketing business in the growing world of sports-betting. We sell sports-betting and casino apps in 10 States across the country. Your first position with us will be signing up people to leading sports and casino apps. This will most likely be in one of our 150 sports and social bars or maybe at a specialised event. You will be excited to present our product to the public and assist them through the app download and sign-up process.
                    </p>
                    <p>
                        We sell sports-betting and casino apps in 10 States across the country. Your first position with us will be signing up people to leading sports and casino apps. This will most likely be in one of our 150 sports and social bars or maybe at a specialised event. You will be excited to present our product to the public and assist them through the app download and sign-up process.
                    </p>
                    <p>
                        You'll be part of a company of young, energetic, ambitious and friendly staff who will help you through your first two weeks of training. Then you will have everything you need so you can master your craft and unleash your potential.
                    </p>

                    <h2>States</h2>
                    <p>
                        We are in the following States and always looking for hard working entrepreneurial sales staff. Please let us know below what States you are interested in:
                    </p>
                    <ul class="text-warning fw-bold">
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

                    <h2>Make Great Money!</h2>
                    <p>
                        <strong class="text-warning">TOP EARNERS MAKE over $1000 PER 20 HOUR WEEK.</strong> The more money you make the happier everyone is. That's why we are constantly adding new products and venues for you to sell in. We don't hire anyone. This is a sales job and you have to sell us on why you would be great at this position. We're looking for people to grow with and are laser focused on being the top dollar earner that we're looking for.
                    </p>

                    <h2>Our Opportunity</h2>
                    <p>
                        We link you to your future career path. We have grown to almost 100 staff in 3 years which creates plenty of growth opportunities for you in both our physical and digital business. This equally applies to the new online sports and casino betting industry. If you prove yourself, the sky is the limit!
                    </p>
                    
                    <div class="d-flex flex-wrap gap-3 mb-5">
                        <span class="badge bg-primary fs-6 px-4 py-3">Apply</span>
                        <span class="badge bg-primary fs-6 px-4 py-3">Interview</span>
                        <span class="badge bg-primary fs-6 px-4 py-3">Training</span>
                        <span class="badge bg-primary fs-6 px-4 py-3">Lift-Off</span>
                    </div>
                </div>

                <!-- Resume Form -->
                <div class="card shadow-lg" style="background-color: #1a2332; border: 2px solid #ffc107;">
                    <div class="card-header py-4" style="background-color: #0d1829; border-bottom: 2px solid #ffc107;">
                        <h3 class="h4 fw-bold text-white mb-0 text-center">
                            <i class="bi bi-file-earmark-person me-2 text-warning"></i>
                            Submit Your Resume
                        </h3>
                    </div>
                    <div class="card-body p-5">
                        <form @submit.prevent="submitForm">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label class="form-label text-white fw-semibold">
                                        <i class="bi bi-person me-1 text-warning"></i>
                                        First Name <span class="text-danger">*</span>
                                    </label>
                                    <input 
                                        v-model="form.first_name" 
                                        type="text" 
                                        class="form-control form-control-lg"
                                        style="background-color: #0d1829; border: 1px solid #2e4057; color: white;"
                                        placeholder="John"
                                        required 
                                    />
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-white fw-semibold">
                                        <i class="bi bi-person me-1 text-warning"></i>
                                        Last Name <span class="text-danger">*</span>
                                    </label>
                                    <input 
                                        v-model="form.last_name" 
                                        type="text" 
                                        class="form-control form-control-lg"
                                        style="background-color: #0d1829; border: 1px solid #2e4057; color: white;"
                                        placeholder="Doe"
                                        required 
                                    />
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-white fw-semibold">
                                        <i class="bi bi-telephone me-1 text-warning"></i>
                                        Phone <span class="text-danger">*</span>
                                    </label>
                                    <input 
                                        v-model="form.phone" 
                                        type="tel" 
                                        class="form-control form-control-lg"
                                        style="background-color: #0d1829; border: 1px solid #2e4057; color: white;"
                                        placeholder="(555) 123-4567"
                                        required 
                                    />
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-white fw-semibold">
                                        <i class="bi bi-envelope me-1 text-warning"></i>
                                        Email <span class="text-danger">*</span>
                                    </label>
                                    <input 
                                        v-model="form.email" 
                                        type="email" 
                                        class="form-control form-control-lg"
                                        style="background-color: #0d1829; border: 1px solid #2e4057; color: white;"
                                        placeholder="john.doe@email.com"
                                        required 
                                    />
                                </div>
                                <div class="col-12">
                                    <label class="form-label text-white fw-semibold">
                                        <i class="bi bi-briefcase me-1 text-warning"></i>
                                        What Are You Applying For?
                                    </label>
                                    <input 
                                        v-model="form.position" 
                                        type="text" 
                                        class="form-control form-control-lg"
                                        style="background-color: #0d1829; border: 1px solid #2e4057; color: white;"
                                        placeholder="e.g., Sales Representative - Arizona"
                                    />
                                </div>
                                <div class="col-12">
                                    <label class="form-label text-white fw-semibold">
                                        <i class="bi bi-chat-square-text me-1 text-warning"></i>
                                        Tell Us About Yourself <span class="text-danger">*</span>
                                    </label>
                                    <textarea 
                                        v-model="form.about" 
                                        class="form-control form-control-lg"
                                        style="background-color: #0d1829; border: 1px solid #2e4057; color: white;"
                                        rows="8"
                                        placeholder="Include your relevant experience, why you're interested in this position, and which states you're available to work in..."
                                        required
                                    ></textarea>
                                    <small class="text-muted d-block mt-2">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Please include which states from our list above you're interested in working in.
                                    </small>
                                </div>
                                <div class="col-12 mt-4">
                                    <button 
                                        type="submit" 
                                        class="btn btn-warning btn-lg w-100 fw-bold py-3 text-uppercase"
                                        style="letter-spacing: 1px; font-size: 1.25rem;"
                                    >
                                        <i class="bi bi-send-fill me-2"></i>
                                        Submit Application
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="mt-5 text-center">
                    <p class="text-muted small">
                        DISCLAIMER: This site is 100% for entertainment purposes only and does not involve real money betting. Gambling can be addictive, please play responsibly. If you or someone you know has a gambling problem and wants help, call 1-800 GAMBLER in the U.S. This service is intended for adult users 21+ only.
                    </p>
                </div>
            </article>
        </div>
    </WelcomeLayout>
</template>

<style scoped>
.article-content {
    color: #e5e7eb;
    font-size: 1.125rem;
    line-height: 1.75;
}

.article-content :deep(h1),
.article-content :deep(h2),
.article-content :deep(h3),
.article-content :deep(h4),
.article-content :deep(h5),
.article-content :deep(h6) {
    color: white;
    font-weight: 600;
    margin-top: 2rem;
    margin-bottom: 1rem;
}

.article-content :deep(h2) {
    font-size: 1.875rem;
}

.article-content :deep(h3) {
    font-size: 1.5rem;
}

.article-content :deep(p) {
    margin-bottom: 1.25rem;
}

.article-content :deep(a) {
    color: #6366F1;
    text-decoration: none;
}

.article-content :deep(a:hover) {
    color: #7C3AED;
    text-decoration: underline;
}

.article-content :deep(ul),
.article-content :deep(ol) {
    margin-bottom: 1.25rem;
    padding-left: 2rem;
}

.article-content :deep(li) {
    margin-bottom: 0.5rem;
}

.form-control {
    transition: all 0.3s ease;
}

.form-control:focus {
    background-color: #0d1829 !important;
    border-color: #ffc107 !important;
    color: white !important;
    box-shadow: 0 0 0 0.25rem rgba(255, 193, 7, 0.25) !important;
}

.form-control::placeholder {
    color: #6c757d;
    opacity: 0.8;
}

.btn-warning:hover {
    background-color: #ffca2c;
    border-color: #ffc720;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(255, 193, 7, 0.3);
}
</style>
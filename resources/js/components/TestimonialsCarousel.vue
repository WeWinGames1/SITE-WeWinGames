<template>
  <section class="py-5 bg-transparent testimonials-section">
    <div class="container">
      <h2 class="display-4 fw-bold text-white text-center mb-2">What Our Users Say</h2>
      <p class="text-center fs-5 text-gray-light mb-5">Join thousands of satisfied bettors</p>
      
      <!-- Carousel Container -->
      <div class="carousel-container position-relative">
        <!-- Navigation Buttons -->
        <button 
          v-if="testimonials.length > 1"
          @click="slideToPrevious"
          class="carousel-nav carousel-nav-prev"
          :disabled="isTransitioning"
        >
          <i class="bi bi-chevron-left"></i>
        </button>

        <button 
          v-if="testimonials.length > 1"
          @click="slideToNext"
          class="carousel-nav carousel-nav-next"
          :disabled="isTransitioning"
        >
          <i class="bi bi-chevron-right"></i>
        </button>

        <!-- Testimonials Track -->
        <div class="carousel-viewport">
          <div 
            class="carousel-track"
            :style="trackStyle"
          >
            <div
              v-for="(testimonial, index) in displayTestimonials"
              :key="`testimonial-${index}`"
              class="testimonial-item"
              :style="itemStyle"
            >
              <div class="card h-100">
                <div class="card-body d-flex flex-column p-4">
                  <div class="d-flex align-items-center mb-4">
                    <div class="position-relative">
                      <div 
                        v-if="testimonial.image"
                        class="rounded-circle overflow-hidden"
                        style="width: 48px; height: 48px; border: 2px solid var(--bs-purple);"
                      >
                        <img
                          :src="testimonial.image"
                          :alt="testimonial.name"
                          class="w-100 h-100 object-fit-cover"
                        />
                      </div>
                      <div 
                        v-else
                        class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center"
                        style="width: 48px; height: 48px; border: 2px solid var(--bs-purple); font-weight: 600;"
                      >
                        {{ getInitials(testimonial.name) }}
                      </div>
                      <div class="position-absolute bottom-0 end-0 bg-success rounded-circle d-flex align-items-center justify-content-center" 
                           style="width: 18px; height: 18px; border: 2px solid var(--bs-card-bg);">
                        <i class="bi bi-check text-white" style="font-size: 10px;"></i>
                      </div>
                    </div>
                    <div class="ms-3">
                      <h6 class="mb-0 text-white fw-semibold">{{ testimonial.name }}</h6>
                      <div v-if="testimonial.title" class="text-gray-light small">{{ testimonial.title }}</div>
                      <div class="d-flex">
                        <i v-for="n in 5" :key="n" 
                           :class="n <= testimonial.stars ? 'bi-star-fill text-warning' : 'bi-star text-gray-light'" 
                           class="bi" 
                           style="font-size: 14px;"></i>
                      </div>
                    </div>
                  </div>
                  <p class="text-gray-light mb-3 flex-grow-1">"{{ testimonial.review }}"</p>
                  <div class="text-gray-light opacity-75 small">
                    <i class="bi bi-patch-check-fill text-purple me-1"></i>
                    Verified Review • {{ testimonial.formatted_date }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Dots Indicator -->
        <div v-if="totalSlides > 1" class="carousel-dots">
          <button
            v-for="i in totalSlides"
            :key="`dot-${i}`"
            @click="slideTo(i - 1)"
            class="carousel-dot"
            :class="{ active: currentSlide === (i - 1) % originalCount }"
            :disabled="isTransitioning"
          ></button>
        </div>
      </div>
      
      <div class="text-center mt-5">
        <a
          href="https://www.google.com/maps/search/?api=1&query=Google&query_place_id=ChIJfaO5JVD--asRNADs2U6y_jc"
          target="_blank"
          class="btn btn-outline-primary btn-lg px-5 py-3"
        >
          <i class="bi bi-star-fill me-2"></i>
          See All Reviews
        </a>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';

interface Testimonial {
  id?: number;
  name: string;
  title?: string;
  stars: number;
  review: string;
  image?: string;
  initials?: string;
  formatted_date: string;
  // Legacy fields
  author?: string;
  rating?: number;
  text?: string;
  avatar?: string;
  date?: string;
}

const props = defineProps<{
  testimonials?: Testimonial[];
}>();

// Default testimonials
const defaultTestimonials = [
  {
    name: "Cameron Zatz-Krecker",
    image: "https://lh3.googleusercontent.com/a-/ALV-UjWTbgj9rIsL68h76xwDbgRtQDLIDO_my5baIVMt0oN6ubczepRIIg=s120-c-rp-mo-br100",
    stars: 5,
    review: "I have been using their golf picks for over a year with consistent results",
    formatted_date: "May 2024",
  },
  {
    name: "Mike McGeough",
    image: "https://lh3.googleusercontent.com/a/ACg8ocK1uKRnMKnRqtN01ABe_wewAGCN-dJ2QcFY_elLk29UFeBELA=s120-c-rp-mo-br100",
    stars: 4,
    review: "A great place to get the best tips for your bets. A little disappointed because the site has been down due to a redesign. Can't wait to see the new look!",
    formatted_date: "May 2024",
  },
  {
    name: "Larry Outlaw",
    stars: 4,
    review: "Site has something for every sports bettor. Love that the site as betting eduction. Great job",
    image: "https://lh3.googleusercontent.com/a/ACg8ocI-_P7xw4dX3Jq9Nhxd_P1Nm-3EnGLuD-4kTRfm9onRXj75Hg=s120-c-rp-mo-br100",
    formatted_date: "May 2024",
  }
];

// State
const currentSlide = ref(0);
const isTransitioning = ref(false);
const itemsPerView = ref(3);
let autoplayInterval: number | null = null;

// Normalize testimonials
const testimonials = computed(() => {
  const raw = props.testimonials && props.testimonials.length > 0 
    ? props.testimonials 
    : defaultTestimonials;
    
  return raw.map(t => ({
    id: t.id,
    name: t.name || t.author || '',
    title: t.title,
    stars: t.stars || t.rating || 5,
    review: t.review || t.text || '',
    image: t.image || t.avatar,
    formatted_date: t.formatted_date || t.date || 'Recent'
  }));
});

const originalCount = computed(() => testimonials.value.length);

// Create display array with duplicates for smooth infinite scroll
const displayTestimonials = computed(() => {
  if (testimonials.value.length === 0) return [];
  
  // If we have fewer testimonials than items per view, duplicate them
  let display = [...testimonials.value];
  while (display.length < itemsPerView.value * 3) {
    display = [...display, ...testimonials.value];
  }
  
  return display;
});

const totalSlides = computed(() => Math.min(originalCount.value, displayTestimonials.value.length));

// Carousel styles
const trackStyle = computed(() => ({
  transform: `translateX(-${currentSlide.value * (100 / itemsPerView.value)}%)`,
  transition: isTransitioning.value ? 'transform 0.5s ease-in-out' : 'none',
  display: 'flex'
}));

const itemStyle = computed(() => ({
  flex: `0 0 ${100 / itemsPerView.value}%`,
  padding: '0 0.5rem'
}));

// Methods
function getInitials(name: string): string {
  const words = name.split(' ').filter(Boolean);
  return words.map(word => word[0]?.toUpperCase()).join('').slice(0, 2) || '??';
}

function slideToNext() {
  if (isTransitioning.value) return;
  
  isTransitioning.value = true;
  currentSlide.value = (currentSlide.value + 1) % displayTestimonials.value.length;
  
  setTimeout(() => {
    isTransitioning.value = false;
  }, 500);
}

function slideToPrevious() {
  if (isTransitioning.value) return;
  
  isTransitioning.value = true;
  currentSlide.value = currentSlide.value - 1;
  if (currentSlide.value < 0) {
    currentSlide.value = displayTestimonials.value.length - 1;
  }
  
  setTimeout(() => {
    isTransitioning.value = false;
  }, 500);
}

function slideTo(index: number) {
  if (isTransitioning.value) return;
  
  isTransitioning.value = true;
  currentSlide.value = index;
  
  setTimeout(() => {
    isTransitioning.value = false;
  }, 500);
}

function updateItemsPerView() {
  if (window.innerWidth < 768) {
    itemsPerView.value = 1;
  } else if (window.innerWidth < 992) {
    itemsPerView.value = 2;
  } else {
    itemsPerView.value = 3;
  }
}

function startAutoplay() {
  stopAutoplay();
  if (testimonials.value.length > 1) {
    autoplayInterval = window.setInterval(() => {
      slideToNext();
    }, 5000);
  }
}

function stopAutoplay() {
  if (autoplayInterval !== null) {
    clearInterval(autoplayInterval);
    autoplayInterval = null;
  }
}

// Lifecycle
onMounted(() => {
  updateItemsPerView();
  window.addEventListener('resize', updateItemsPerView);
  startAutoplay();
});

onUnmounted(() => {
  window.removeEventListener('resize', updateItemsPerView);
  stopAutoplay();
});
</script>

<style scoped>
.testimonials-section {
  overflow: hidden;
}

.carousel-container {
  position: relative;
  padding: 0 50px;
}

.carousel-viewport {
  overflow: hidden;
  position: relative;
}

.carousel-track {
  display: flex;
  transition: transform 0.5s ease-in-out;
}

.testimonial-item {
  flex-shrink: 0;
}

.carousel-nav {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  width: 50px;
  height: 50px;
  border-radius: 50%;
  background-color: rgba(0, 0, 0, 0.5);
  border: 1px solid rgba(255, 255, 255, 0.3);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  z-index: 10;
  transition: all 0.3s ease;
}

.carousel-nav:hover:not(:disabled) {
  background-color: rgba(255, 255, 255, 0.1);
  border-color: rgba(255, 255, 255, 0.5);
}

.carousel-nav:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.carousel-nav-prev {
  left: 0;
}

.carousel-nav-next {
  right: 0;
}

.carousel-dots {
  display: flex;
  justify-content: center;
  gap: 0.5rem;
  margin-top: 1.5rem;
}

.carousel-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  border: none;
  background-color: var(--bs-primary);
  opacity: 0.5;
  cursor: pointer;
  transition: opacity 0.3s ease;
  padding: 0;
}

.carousel-dot.active {
  opacity: 1;
}

.carousel-dot:disabled {
  cursor: not-allowed;
}

.card {
  background-color: var(--bs-card-bg);
  border: 1px solid var(--bs-card-border);
  height: 100%;
}

/* Responsive adjustments */
@media (max-width: 767px) {
  .carousel-container {
    padding: 0 40px;
  }
  
  .carousel-nav {
    width: 40px;
    height: 40px;
  }
  
  .carousel-nav-prev {
    left: -10px;
  }
  
  .carousel-nav-next {
    right: -10px;
  }
}

/* Pause on hover */
.carousel-viewport:hover ~ * {
  animation-play-state: paused;
}
</style>
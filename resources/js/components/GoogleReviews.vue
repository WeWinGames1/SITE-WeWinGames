<template>
  <section class="py-5 bg-transparent google-reviews-section">
    <div class="container">
      <h2 class="display-4 fw-bold text-white text-center mb-2">What Our Users Say</h2>
      <p class="text-center fs-5 text-gray-light mb-5">Join thousands of satisfied bettors</p>
      <div class="row g-4 justify-content-center">
        <div
          v-for="review in reviews"
          :key="review.author"
          class="col-lg-4"
        >
          <div class="card h-100" style="background-color: var(--bs-card-bg); border: 1px solid var(--bs-card-border);">
            <div class="card-body d-flex flex-column p-4">
              <div class="d-flex align-items-center mb-4">
                <div class="position-relative">
                  <img
                    :src="review.avatar"
                    alt="avatar"
                    class="rounded-circle"
                    style="width: 48px; height: 48px; object-fit: cover; border: 2px solid var(--bs-purple);"
                  />
                  <div class="position-absolute bottom-0 end-0 bg-success rounded-circle d-flex align-items-center justify-content-center" 
                       style="width: 18px; height: 18px; border: 2px solid var(--bs-card-bg);">
                    <i class="bi bi-check text-white" style="font-size: 10px;"></i>
                  </div>
                </div>
                <div class="ms-3">
                  <h6 class="mb-0 text-white fw-semibold">{{ review.author }}</h6>
                  <div class="d-flex">
                    <i v-for="n in 5" :key="n" 
                       :class="n <= review.rating ? 'bi-star-fill text-warning' : 'bi-star text-gray-light'" 
                       class="bi" 
                       style="font-size: 14px;"></i>
                  </div>
                </div>
              </div>
              <p class="text-gray-light mb-3 flex-grow-1">"{{ review.text }}"</p>
              <div class="text-gray-light opacity-75 small">
                <i class="bi bi-patch-check-fill text-purple me-1"></i>
                Verified Review • {{ review.date }}
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="text-center mt-5">
        <a
          href="https://www.google.com/maps/search/?api=1&query=Google&query_place_id=ChIJfaO5JVD--asRNADs2U6y_jc"
          target="_blank"
          class="btn btn-outline-primary btn-lg px-5 py-3"
        >
          <i class="bi bi-google me-2"></i>
          See All Google Reviews
        </a>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
const reviews = [
  {
    author: "Cameron Zatz-Krecker",
    avatar: "https://lh3.googleusercontent.com/a-/ALV-UjWTbgj9rIsL68h76xwDbgRtQDLIDO_my5baIVMt0oN6ubczepRIIg=s120-c-rp-mo-br100",
    rating: 5,
    text: "I have been using their golf picks for over a year with consistent results",
    date: "May 2024",
  },
  {
    author: "Mike McGeough",
    avatar: "https://lh3.googleusercontent.com/a/ACg8ocK1uKRnMKnRqtN01ABe_wewAGCN-dJ2QcFY_elLk29UFeBELA=s120-c-rp-mo-br100",
    rating: 4,
    text: "A great place to get the best tips for your bets. A little disappointed because the site has been down due to a redesign. Can't wait to see the new look!",
    date: "May 2024",
  },
  {
    author: "Larry Outlaw",
    rating: 4,
    text: "Site has something for every sports bettor. Love that the site as betting eduction. Great job",
    avatar: "https://lh3.googleusercontent.com/a/ACg8ocI-_P7xw4dX3Jq9Nhxd_P1Nm-3EnGLuD-4kTRfm9onRXj75Hg=s120-c-rp-mo-br100",
    date: "May 2024",
  }
];
</script>
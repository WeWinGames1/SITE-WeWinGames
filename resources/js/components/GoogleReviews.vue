<template>
  <section class="py-16 bg-transparent">
    <div class="container mx-auto px-4">
      <h2 class="text-3xl font-bold text-white text-center mb-8">What Our Customers Say</h2>
      <div class="flex flex-col md:flex-row gap-6 max-w-4xl mx-auto">
        <div
          v-for="review in reviews"
          :key="review.author"
          class="bg-gray-800 rounded-lg shadow p-6 flex-1 flex flex-col"
        >
          <div class="flex items-center mb-2">
            <img
              :src="review.avatar"
              alt="avatar"
              class="w-10 h-10 rounded-full mr-3 border-2 border-indigo-500"
            />
            <div>
              <div class="font-bold text-white">{{ review.author }}</div>
              <div class="flex items-center">
                <span v-for="n in 5" :key="n">
                  <svg
                    v-if="n <= review.rating"
                    class="w-4 h-4 text-yellow-400 inline"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                  >
                    <path
                      d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.967a1 1 0 00.95.69h4.178c.969 0 1.371 1.24.588 1.81l-3.385 2.46a1 1 0 00-.364 1.118l1.287 3.966c.3.922-.755 1.688-1.54 1.118l-3.385-2.46a1 1 0 00-1.175 0l-3.385 2.46c-.784.57-1.838-.196-1.539-1.118l1.287-3.966a1 1 0 00-.364-1.118l-3.385-2.46c-.783-.57-.38-1.81.588-1.81h4.178a1 1 0 00.95-.69l1.286-3.967z"
                    />
                  </svg>
                  <svg
                    v-else
                    class="w-4 h-4 text-gray-500 inline"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                  >
                    <path
                      d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.967a1 1 0 00.95.69h4.178c.969 0 1.371 1.24.588 1.81l-3.385 2.46a1 1 0 00-.364 1.118l1.287 3.966c.3.922-.755 1.688-1.54 1.118l-3.385-2.46a1 1 0 00-1.175 0l-3.385 2.46c-.784.57-1.838-.196-1.539-1.118l1.287-3.966a1 1 0 00-.364-1.118l-3.385-2.46c-.783-.57-.38-1.81.588-1.81h4.178a1 1 0 00.95-.69l1.286-3.967z"
                    />
                  </svg>
                </span>
              </div>
            </div>
          </div>
          <p class="text-gray-200 mt-2 flex-1">"{{ review.text }}"</p>
          <div class="mt-4 text-xs text-gray-400">{{ review.date }}</div>
        </div>
      </div>
      <div class="text-center mt-8">
        <a
          href="https://www.google.com/maps/search/?api=1&query=Google&query_place_id=ChIJfaO5JVD--asRNADs2U6y_jc"
          target="_blank"
          class="inline-block bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-6 rounded transition"
        >
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
    text: "A great place to get the best tips for your bets. A little disappointed because the site has been down due to a redesign. Can’t wait to see the new look!",
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
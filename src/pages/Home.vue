<script setup lang="ts">
import { useAuthStore } from '@/stores/auth';

const auth = useAuthStore();
</script>

<template>
  <div class="space-y-16 py-8">
    <!-- Hero -->
    <section class="text-center">
      <h1 class="text-4xl font-bold tracking-tight text-gray-900 sm:text-5xl">
        Your recipes,
        <span class="text-primary-600">organised</span>.
      </h1>
      <p class="mx-auto mt-4 max-w-2xl text-lg text-gray-600">
        Discover, create, and manage recipes with ingredients, directions, shopping lists, and pantry tracking — all in one place.
      </p>
      <div class="mt-8 flex items-center justify-center gap-4">
        <RouterLink
          to="/recipes"
          class="rounded-lg bg-primary-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700"
        >
          Browse Recipes
        </RouterLink>
        <RouterLink
          v-if="!auth.isAuthenticated"
          to="/register"
          class="rounded-lg border border-gray-300 bg-white px-6 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50"
        >
          Create Account
        </RouterLink>
      </div>
    </section>

    <!-- Feature highlights -->
    <section class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
      <div
        v-for="feature in features"
        :key="feature.title"
        class="rounded-xl border border-gray-200 bg-white p-6"
      >
        <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-lg bg-primary-50 text-primary-600">
          <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" :d="feature.icon" />
          </svg>
        </div>
        <h3 class="text-base font-semibold text-gray-900">{{ feature.title }}</h3>
        <p class="mt-1 text-sm text-gray-600">{{ feature.description }}</p>
      </div>
    </section>
  </div>
</template>

<script lang="ts">
const features = [
  {
    title: 'Recipe Management',
    description: 'Create, fork, import and export recipes with full ingredient and direction tracking.',
    icon: 'M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25',
  },
  {
    title: 'Collections & Menus',
    description: 'Organise recipes into bags or scheduled menus with meal planning support.',
    icon: 'M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z',
  },
  {
    title: 'Shopping Lists',
    description: 'Build shopping lists linked to recipes and check off items as you go.',
    icon: 'M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z',
  },
  {
    title: 'Pantry Tracking',
    description: 'Track what you have at home with expiry dates and "what can I cook?" matching.',
    icon: 'M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z',
  },
  {
    title: 'Search & Discover',
    description: 'Full-text search across recipes, autocomplete suggestions, and filtered browsing.',
    icon: 'M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z',
  },
  {
    title: 'Ratings & Comments',
    description: 'Rate recipes, leave threaded comments, and follow your favourite authors.',
    icon: 'M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z',
  },
];
</script>

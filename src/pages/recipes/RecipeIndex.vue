<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { apiClient } from '@/api/client';
import { deserialize } from '@/api/jsonapi';
import type { Recipe, Pagination } from '@/types';

const router = useRouter();
const route = useRoute();

const recipes = ref<Recipe[]>([]);
const pagination = ref<Pagination | null>(null);
const loading = ref(false);
const search = ref((route.query.q as string) || '');
const currentPage = ref(Number(route.query.page) || 1);

async function fetchRecipes() {
  loading.value = true;
  try {
    const endpoint = search.value ? '/v1/recipes/search' : '/v1/recipes';
    const params: Record<string, unknown> = {
      'page[number]': currentPage.value,
      'page[size]': 12,
    };
    if (search.value) params.q = search.value;

    const response = await apiClient.get(endpoint, { params });
    const result = deserialize(response.data);

    recipes.value = (Array.isArray(result.data) ? result.data : [result.data]) as unknown as Recipe[];
    pagination.value = (result.meta?.page as Pagination) || null;
  } catch {
    recipes.value = [];
  } finally {
    loading.value = false;
  }
}

function handleSearch() {
  currentPage.value = 1;
  updateQuery();
}

function goToPage(page: number) {
  currentPage.value = page;
  updateQuery();
}

function updateQuery() {
  const query: Record<string, string> = {};
  if (search.value) query.q = search.value;
  if (currentPage.value > 1) query.page = String(currentPage.value);
  router.push({ query });
}

watch(() => route.query, () => {
  search.value = (route.query.q as string) || '';
  currentPage.value = Number(route.query.page) || 1;
  fetchRecipes();
});

onMounted(fetchRecipes);
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <h1 class="text-2xl font-bold text-gray-900">Recipes</h1>

      <form class="flex gap-2" @submit.prevent="handleSearch">
        <input
          v-model="search"
          type="search"
          placeholder="Search recipes..."
          class="w-64 rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none"
        />
        <button
          type="submit"
          class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-primary-700"
        >
          Search
        </button>
      </form>
    </div>

    <div v-if="loading" class="py-12 text-center text-gray-500">Loading recipes...</div>

    <div v-else-if="recipes.length === 0" class="py-12 text-center text-gray-500">
      {{ search ? 'No recipes found for your search.' : 'No recipes available yet.' }}
    </div>

    <div v-else class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
      <RouterLink
        v-for="recipe in recipes"
        :key="recipe.id"
        :to="{ name: 'recipe', params: { slug: recipe.slug } }"
        class="group rounded-xl border border-gray-200 bg-white p-5 transition hover:border-primary-300 hover:shadow-sm"
      >
        <div class="mb-3 flex items-center gap-2">
          <span
            class="rounded-full px-2 py-0.5 text-xs font-medium"
            :class="{
              'bg-green-50 text-green-700': recipe.status === 'published',
              'bg-yellow-50 text-yellow-700': recipe.status === 'draft',
              'bg-gray-100 text-gray-600': recipe.status === 'archived',
            }"
          >
            {{ recipe.status }}
          </span>
          <span v-if="recipe.difficulty" class="text-xs text-gray-500">
            {{ recipe.difficulty }}
          </span>
        </div>

        <h3 class="font-semibold text-gray-900 group-hover:text-primary-600">
          {{ recipe.title }}
        </h3>

        <p v-if="recipe.description" class="mt-1 line-clamp-2 text-sm text-gray-600">
          {{ recipe.description }}
        </p>

        <div class="mt-3 flex items-center gap-4 text-xs text-gray-500">
          <span v-if="recipe['prep-time-minutes']">
            {{ recipe['prep-time-minutes'] }} min
          </span>
          <span v-if="recipe.serves">
            Serves {{ recipe.serves }}
          </span>
        </div>
      </RouterLink>
    </div>

    <!-- Pagination -->
    <nav v-if="pagination && pagination['last-page'] > 1" class="flex items-center justify-center gap-2 pt-4">
      <button
        v-for="page in pagination['last-page']"
        :key="page"
        :disabled="page === currentPage"
        class="rounded-lg px-3 py-1.5 text-sm font-medium transition"
        :class="
          page === currentPage
            ? 'bg-primary-600 text-white'
            : 'text-gray-600 hover:bg-gray-100'
        "
        @click="goToPage(page)"
      >
        {{ page }}
      </button>
    </nav>
  </div>
</template>

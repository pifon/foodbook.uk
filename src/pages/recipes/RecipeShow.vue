<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { apiClient } from '@/api/client';
import { deserialize } from '@/api/jsonapi';
import type { Recipe } from '@/types';

const props = defineProps<{ slug: string }>();
const router = useRouter();

const recipe = ref<Recipe | null>(null);
const preparation = ref<{ ingredients: unknown[]; directions: unknown[] } | null>(null);
const loading = ref(true);
const error = ref('');

async function fetchRecipe() {
  loading.value = true;
  error.value = '';
  try {
    const [recipeRes, prepRes] = await Promise.all([
      apiClient.get(`/v1/recipes/${props.slug}`),
      apiClient.get(`/v1/recipes/${props.slug}/preparation`).catch(() => null),
    ]);

    const result = deserialize(recipeRes.data);
    recipe.value = result.data as unknown as Recipe;

    if (prepRes) {
      preparation.value = prepRes.data.data?.attributes || prepRes.data;
    }
  } catch (e: unknown) {
    const status = (e as { response?: { status?: number } })?.response?.status;
    error.value = status === 404 ? 'Recipe not found.' : 'Failed to load recipe.';
  } finally {
    loading.value = false;
  }
}

onMounted(fetchRecipe);
</script>

<template>
  <div>
    <button
      class="mb-6 flex items-center gap-1 text-sm text-gray-600 transition hover:text-gray-900"
      @click="router.back()"
    >
      <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
      </svg>
      Back
    </button>

    <div v-if="loading" class="py-12 text-center text-gray-500">Loading recipe...</div>

    <div v-else-if="error" class="py-12 text-center text-red-600">{{ error }}</div>

    <div v-else-if="recipe" class="space-y-8">
      <!-- Header -->
      <div>
        <div class="mb-2 flex items-center gap-2">
          <span
            class="rounded-full px-2.5 py-0.5 text-xs font-medium"
            :class="{
              'bg-green-50 text-green-700': recipe.status === 'published',
              'bg-yellow-50 text-yellow-700': recipe.status === 'draft',
              'bg-gray-100 text-gray-600': recipe.status === 'archived',
            }"
          >
            {{ recipe.status }}
          </span>
          <span v-if="recipe.difficulty" class="text-sm text-gray-500">{{ recipe.difficulty }}</span>
        </div>

        <h1 class="text-3xl font-bold text-gray-900">{{ recipe.title }}</h1>

        <p v-if="recipe.description" class="mt-2 text-gray-600">{{ recipe.description }}</p>

        <div class="mt-4 flex flex-wrap gap-4 text-sm text-gray-500">
          <span v-if="recipe['prep-time-minutes']" class="flex items-center gap-1">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {{ recipe['prep-time-minutes'] }} min
          </span>
          <span v-if="recipe.serves" class="flex items-center gap-1">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
            </svg>
            Serves {{ recipe.serves }}
          </span>
        </div>
      </div>

      <!-- Ingredients -->
      <section v-if="preparation?.ingredients" class="rounded-xl border border-gray-200 bg-white p-6">
        <h2 class="mb-4 text-lg font-semibold text-gray-900">Ingredients</h2>
        <ul class="space-y-2">
          <li
            v-for="(ingredient, idx) in (preparation.ingredients as Record<string, unknown>[])"
            :key="idx"
            class="flex items-baseline gap-2 text-sm"
          >
            <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-primary-400" />
            <span>
              <span v-if="ingredient.amount" class="font-medium">{{ ingredient.amount }}</span>
              <span v-if="ingredient.measure" class="text-gray-500">{{ (ingredient.measure as Record<string, unknown>)?.abbreviation || ingredient.measure }}</span>
              <span class="text-gray-900">{{ (ingredient.product as Record<string, unknown>)?.name || ingredient.product }}</span>
              <span v-if="ingredient.notes" class="text-gray-500"> — {{ ingredient.notes }}</span>
            </span>
          </li>
        </ul>
      </section>

      <!-- Directions -->
      <section v-if="preparation?.directions" class="rounded-xl border border-gray-200 bg-white p-6">
        <h2 class="mb-4 text-lg font-semibold text-gray-900">Directions</h2>
        <ol class="space-y-4">
          <li
            v-for="(direction, idx) in (preparation.directions as Record<string, unknown>[])"
            :key="idx"
            class="flex gap-4 text-sm"
          >
            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-primary-50 text-xs font-semibold text-primary-700">
              {{ idx + 1 }}
            </span>
            <div>
              <p class="text-gray-900">
                <span v-if="direction.operation" class="font-medium">{{ (direction.operation as Record<string, unknown>)?.name || direction.operation }}</span>
                <span v-if="direction.product"> {{ (direction.product as Record<string, unknown>)?.name || direction.product }}</span>
              </p>
              <p v-if="direction.notes" class="mt-0.5 text-gray-500">{{ direction.notes }}</p>
              <p v-if="direction.duration" class="mt-0.5 text-xs text-gray-400">{{ direction.duration }} min</p>
            </div>
          </li>
        </ol>
      </section>
    </div>
  </div>
</template>

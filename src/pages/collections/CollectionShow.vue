<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { apiClient } from '@/api/client';
import { deserialize } from '@/api/jsonapi';
import type { Collection } from '@/types';

const props = defineProps<{ id: string }>();
const router = useRouter();

const collection = ref<Collection | null>(null);
const loading = ref(true);

async function fetchCollection() {
  loading.value = true;
  try {
    const response = await apiClient.get(`/v1/collections/${props.id}`);
    const result = deserialize(response.data);
    collection.value = result.data as unknown as Collection;
  } catch {
    collection.value = null;
  } finally {
    loading.value = false;
  }
}

onMounted(fetchCollection);
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

    <div v-if="loading" class="py-12 text-center text-gray-500">Loading collection...</div>

    <div v-else-if="collection" class="space-y-6">
      <div>
        <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">
          {{ collection.type }}
        </span>
        <h1 class="mt-2 text-2xl font-bold text-gray-900">{{ collection.name }}</h1>
        <p v-if="collection.description" class="mt-1 text-gray-600">{{ collection.description }}</p>
      </div>

      <div v-if="collection.items && collection.items.length > 0" class="space-y-3">
        <div
          v-for="item in collection.items"
          :key="item.id"
          class="rounded-lg border border-gray-200 bg-white p-4"
        >
          <RouterLink
            v-if="item.recipe"
            :to="{ name: 'recipe', params: { slug: item.recipe.slug } }"
            class="font-medium text-primary-600 hover:text-primary-700"
          >
            {{ item.recipe.title }}
          </RouterLink>
          <p v-if="item.notes" class="mt-1 text-sm text-gray-500">{{ item.notes }}</p>
        </div>
      </div>
      <p v-else class="text-gray-500">This collection is empty.</p>
    </div>
  </div>
</template>

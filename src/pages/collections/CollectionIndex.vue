<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { apiClient } from '@/api/client';
import { deserialize } from '@/api/jsonapi';
import type { Collection } from '@/types';

const collections = ref<Collection[]>([]);
const loading = ref(true);

async function fetchCollections() {
  loading.value = true;
  try {
    const response = await apiClient.get('/v1/collections');
    const result = deserialize(response.data);
    collections.value = (Array.isArray(result.data) ? result.data : []) as unknown as Collection[];
  } catch {
    collections.value = [];
  } finally {
    loading.value = false;
  }
}

onMounted(fetchCollections);
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold text-gray-900">Collections</h1>
    </div>

    <div v-if="loading" class="py-12 text-center text-gray-500">Loading collections...</div>

    <div v-else-if="collections.length === 0" class="py-12 text-center text-gray-500">
      No collections yet. Create one to organise your recipes.
    </div>

    <div v-else class="grid gap-4 sm:grid-cols-2">
      <RouterLink
        v-for="collection in collections"
        :key="collection.id"
        :to="{ name: 'collection', params: { id: collection.id } }"
        class="group rounded-xl border border-gray-200 bg-white p-5 transition hover:border-primary-300 hover:shadow-sm"
      >
        <div class="mb-1 flex items-center gap-2">
          <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">
            {{ collection.type }}
          </span>
        </div>
        <h3 class="font-semibold text-gray-900 group-hover:text-primary-600">{{ collection.name }}</h3>
        <p v-if="collection.description" class="mt-1 text-sm text-gray-600">{{ collection.description }}</p>
      </RouterLink>
    </div>
  </div>
</template>

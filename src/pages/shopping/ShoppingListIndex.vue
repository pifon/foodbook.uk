<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { apiClient } from '@/api/client';
import { deserialize } from '@/api/jsonapi';
import type { ShoppingList } from '@/types';

const lists = ref<ShoppingList[]>([]);
const loading = ref(true);

async function fetchLists() {
  loading.value = true;
  try {
    const response = await apiClient.get('/v1/shopping-lists');
    const result = deserialize(response.data);
    lists.value = (Array.isArray(result.data) ? result.data : []) as unknown as ShoppingList[];
  } catch {
    lists.value = [];
  } finally {
    loading.value = false;
  }
}

onMounted(fetchLists);
</script>

<template>
  <div class="space-y-6">
    <h1 class="text-2xl font-bold text-gray-900">Shopping Lists</h1>

    <div v-if="loading" class="py-12 text-center text-gray-500">Loading shopping lists...</div>

    <div v-else-if="lists.length === 0" class="py-12 text-center text-gray-500">
      No shopping lists yet.
    </div>

    <div v-else class="space-y-3">
      <RouterLink
        v-for="list in lists"
        :key="list.id"
        :to="{ name: 'shopping-list', params: { id: list.id } }"
        class="group flex items-center justify-between rounded-xl border border-gray-200 bg-white p-5 transition hover:border-primary-300 hover:shadow-sm"
      >
        <div>
          <h3 class="font-semibold text-gray-900 group-hover:text-primary-600">{{ list.name }}</h3>
          <p class="text-sm text-gray-500">{{ list.status }}</p>
        </div>
        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
        </svg>
      </RouterLink>
    </div>
  </div>
</template>

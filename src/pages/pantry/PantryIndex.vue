<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { apiClient } from '@/api/client';
import { deserialize } from '@/api/jsonapi';
import type { PantryItem } from '@/types';

const items = ref<PantryItem[]>([]);
const loading = ref(true);

async function fetchPantry() {
  loading.value = true;
  try {
    const response = await apiClient.get('/v1/pantry');
    const result = deserialize(response.data);
    items.value = (Array.isArray(result.data) ? result.data : []) as unknown as PantryItem[];
  } catch {
    items.value = [];
  } finally {
    loading.value = false;
  }
}

onMounted(fetchPantry);
</script>

<template>
  <div class="space-y-6">
    <h1 class="text-2xl font-bold text-gray-900">Pantry</h1>

    <div v-if="loading" class="py-12 text-center text-gray-500">Loading pantry...</div>

    <div v-else-if="items.length === 0" class="py-12 text-center text-gray-500">
      Your pantry is empty. Add items to track what you have at home.
    </div>

    <div v-else class="overflow-hidden rounded-xl border border-gray-200 bg-white">
      <table class="w-full text-left text-sm">
        <thead class="border-b border-gray-200 bg-gray-50">
          <tr>
            <th class="px-4 py-3 font-medium text-gray-700">Product</th>
            <th class="px-4 py-3 font-medium text-gray-700">Quantity</th>
            <th class="px-4 py-3 font-medium text-gray-700">Expiry</th>
            <th class="px-4 py-3 font-medium text-gray-700">Status</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="item in items" :key="item.id">
            <td class="px-4 py-3 font-medium text-gray-900">
              {{ item.product?.name || 'Unknown' }}
            </td>
            <td class="px-4 py-3 text-gray-600">
              {{ item.quantity || '—' }}
              {{ item.measure?.abbreviation || '' }}
            </td>
            <td class="px-4 py-3 text-gray-600">
              {{ item['expiry-date'] || '—' }}
            </td>
            <td class="px-4 py-3">
              <span
                v-if="item['is-expired']"
                class="rounded-full bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700"
              >
                Expired
              </span>
              <span
                v-else-if="item['is-past-best-before']"
                class="rounded-full bg-yellow-50 px-2 py-0.5 text-xs font-medium text-yellow-700"
              >
                Past best before
              </span>
              <span
                v-else
                class="rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700"
              >
                Fresh
              </span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

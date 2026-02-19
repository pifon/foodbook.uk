<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { apiClient } from '@/api/client';
import { deserialize } from '@/api/jsonapi';
import type { ShoppingList } from '@/types';

const props = defineProps<{ id: string }>();
const router = useRouter();

const list = ref<ShoppingList | null>(null);
const items = ref<Record<string, unknown>[]>([]);
const loading = ref(true);

async function fetchList() {
  loading.value = true;
  try {
    const [listRes, itemsRes] = await Promise.all([
      apiClient.get(`/v1/shopping-lists/${props.id}`),
      apiClient.get(`/v1/shopping-lists/${props.id}/items`),
    ]);

    const listResult = deserialize(listRes.data);
    list.value = listResult.data as unknown as ShoppingList;

    const itemsResult = deserialize(itemsRes.data);
    items.value = (Array.isArray(itemsResult.data) ? itemsResult.data : []) as Record<string, unknown>[];
  } catch {
    list.value = null;
  } finally {
    loading.value = false;
  }
}

async function toggleItem(itemId: string, checked: boolean) {
  await apiClient.patch(`/v1/shopping-lists/${props.id}/items/${itemId}`, {
    data: { type: 'shopping-list-items', id: itemId, attributes: { checked: !checked } },
  });
  await fetchList();
}

onMounted(fetchList);
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

    <div v-if="loading" class="py-12 text-center text-gray-500">Loading...</div>

    <div v-else-if="list" class="space-y-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ list.name }}</h1>
        <span
          class="mt-1 inline-block rounded-full px-2.5 py-0.5 text-xs font-medium"
          :class="{
            'bg-green-50 text-green-700': list.status === 'active',
            'bg-blue-50 text-blue-700': list.status === 'completed',
            'bg-gray-100 text-gray-600': list.status === 'archived',
          }"
        >
          {{ list.status }}
        </span>
      </div>

      <ul v-if="items.length > 0" class="divide-y divide-gray-200 rounded-xl border border-gray-200 bg-white">
        <li
          v-for="item in items"
          :key="(item.id as string)"
          class="flex items-center gap-3 px-4 py-3"
        >
          <button
            class="flex h-5 w-5 shrink-0 items-center justify-center rounded border transition"
            :class="item.checked ? 'border-primary-600 bg-primary-600' : 'border-gray-300 hover:border-primary-400'"
            @click="toggleItem(item.id as string, item.checked as boolean)"
          >
            <svg v-if="item.checked" class="h-3 w-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
              <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
            </svg>
          </button>
          <span :class="item.checked ? 'text-gray-400 line-through' : 'text-gray-900'">
            <span v-if="item.quantity">{{ item.quantity }} </span>
            {{ (item.product as Record<string, unknown>)?.name || 'Item' }}
          </span>
        </li>
      </ul>
      <p v-else class="text-gray-500">This list is empty.</p>
    </div>
  </div>
</template>

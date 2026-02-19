<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { apiClient } from '@/api/client';
import { deserialize } from '@/api/jsonapi';

const auth = useAuthStore();

const preferences = ref<Record<string, unknown> | null>(null);
const loading = ref(true);
const message = ref('');

async function fetchPreferences() {
  loading.value = true;
  try {
    const response = await apiClient.get('/v1/me/preferences');
    const result = deserialize(response.data);
    preferences.value = result.data as Record<string, unknown>;
  } catch {
    preferences.value = null;
  } finally {
    loading.value = false;
  }
}

async function updatePreferences() {
  if (!preferences.value) return;
  message.value = '';
  try {
    await apiClient.patch('/v1/me/preferences', {
      data: {
        type: 'user-preferences',
        attributes: preferences.value,
      },
    });
    message.value = 'Preferences updated.';
  } catch {
    message.value = 'Failed to update preferences.';
  }
}

onMounted(fetchPreferences);
</script>

<template>
  <div class="space-y-8">
    <div>
      <h1 class="text-2xl font-bold text-gray-900">Settings</h1>
      <p class="mt-1 text-gray-600">Manage your account and preferences.</p>
    </div>

    <!-- Profile -->
    <section class="rounded-xl border border-gray-200 bg-white p-6">
      <h2 class="mb-4 text-lg font-semibold text-gray-900">Profile</h2>
      <dl class="space-y-3 text-sm">
        <div class="flex gap-3">
          <dt class="w-28 shrink-0 font-medium text-gray-500">Username</dt>
          <dd class="text-gray-900">{{ auth.user?.username }}</dd>
        </div>
        <div class="flex gap-3">
          <dt class="w-28 shrink-0 font-medium text-gray-500">Email</dt>
          <dd class="text-gray-900">{{ auth.user?.email }}</dd>
        </div>
      </dl>
    </section>

    <!-- Preferences -->
    <section class="rounded-xl border border-gray-200 bg-white p-6">
      <h2 class="mb-4 text-lg font-semibold text-gray-900">Preferences</h2>

      <div v-if="loading" class="text-sm text-gray-500">Loading preferences...</div>

      <form v-else-if="preferences" class="space-y-4" @submit.prevent="updatePreferences">
        <div>
          <label class="block text-sm font-medium text-gray-700">Spice tolerance</label>
          <select
            v-model="preferences['spice-tolerance']"
            class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none"
          >
            <option :value="1">Mild</option>
            <option :value="2">Medium</option>
            <option :value="3">Hot</option>
            <option :value="4">Very hot</option>
            <option :value="5">Extreme</option>
          </select>
        </div>

        <div v-if="message" class="text-sm" :class="message.includes('Failed') ? 'text-red-600' : 'text-green-600'">
          {{ message }}
        </div>

        <button
          type="submit"
          class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-primary-700"
        >
          Save preferences
        </button>
      </form>

      <p v-else class="text-sm text-gray-500">Could not load preferences.</p>
    </section>
  </div>
</template>

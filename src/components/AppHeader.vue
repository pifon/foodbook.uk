<script setup lang="ts">
import { useAuthStore } from '@/stores/auth';
import { useRouter } from 'vue-router';

defineEmits<{ 'toggle-sidebar': [] }>();

const auth = useAuthStore();
const router = useRouter();

async function handleLogout() {
  auth.logout();
  await router.push({ name: 'home' });
}
</script>

<template>
  <header class="sticky top-0 z-40 border-b border-gray-200 bg-white">
    <div class="flex h-16 items-center gap-4 px-4 sm:px-6 lg:px-8">
      <button
        v-if="auth.isAuthenticated"
        class="rounded-md p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700 lg:hidden"
        @click="$emit('toggle-sidebar')"
      >
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
        </svg>
      </button>

      <RouterLink to="/" class="text-xl font-bold text-primary-600">
        Foodbook
      </RouterLink>

      <nav class="ml-8 hidden items-center gap-6 sm:flex">
        <RouterLink
          to="/recipes"
          class="text-sm font-medium text-gray-600 transition hover:text-gray-900"
          active-class="text-primary-600"
        >
          Recipes
        </RouterLink>
      </nav>

      <div class="ml-auto flex items-center gap-3">
        <template v-if="auth.isAuthenticated">
          <span class="hidden text-sm text-gray-600 sm:inline">
            {{ auth.user?.username }}
          </span>
          <button
            class="rounded-lg px-3 py-1.5 text-sm font-medium text-gray-600 transition hover:bg-gray-100 hover:text-gray-900"
            @click="handleLogout"
          >
            Log out
          </button>
        </template>
        <template v-else>
          <RouterLink
            to="/login"
            class="rounded-lg px-3 py-1.5 text-sm font-medium text-gray-600 transition hover:bg-gray-100"
          >
            Log in
          </RouterLink>
          <RouterLink
            to="/register"
            class="rounded-lg bg-primary-600 px-4 py-1.5 text-sm font-medium text-white transition hover:bg-primary-700"
          >
            Sign up
          </RouterLink>
        </template>
      </div>
    </div>
  </header>
</template>

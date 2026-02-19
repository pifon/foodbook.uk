<script setup lang="ts">
import AppHeader from '@/components/AppHeader.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import { useAuthStore } from '@/stores/auth';
import { ref } from 'vue';

const auth = useAuthStore();
const sidebarOpen = ref(false);
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <AppHeader @toggle-sidebar="sidebarOpen = !sidebarOpen" />

    <div class="flex">
      <AppSidebar v-if="auth.isAuthenticated" :open="sidebarOpen" @close="sidebarOpen = false" />

      <main class="flex-1 px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-5xl">
          <RouterView />
        </div>
      </main>
    </div>
  </div>
</template>

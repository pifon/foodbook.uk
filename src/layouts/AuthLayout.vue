<script setup lang="ts">
</script>

<template>
  <div class="flex min-h-screen items-center justify-center bg-gray-50 px-4">
    <div class="w-full max-w-md">
      <div class="mb-8 text-center">
        <RouterLink to="/" class="text-3xl font-bold text-primary-600">
          Foodbook
        </RouterLink>
      </div>

      <div class="rounded-xl bg-white p-8 shadow-sm ring-1 ring-gray-200">
        <RouterView />
      </div>
    </div>
  </div>
</template>

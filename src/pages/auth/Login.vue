<script setup lang="ts">
import { ref } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const auth = useAuthStore();
const router = useRouter();
const route = useRoute();

const form = ref({ username: '', password: '' });
const error = ref('');

async function handleSubmit() {
  error.value = '';
  try {
    await auth.login(form.value.username, form.value.password);
    const redirect = (route.query.redirect as string) || '/dashboard';
    await router.push(redirect);
  } catch (e: unknown) {
    const msg =
      (e as { response?: { data?: { message?: string } } })?.response?.data?.message;
    error.value = msg || 'Invalid credentials. Please try again.';
  }
}
</script>

<template>
  <div>
    <h2 class="text-xl font-semibold text-gray-900">Log in to your account</h2>
    <p class="mt-1 text-sm text-gray-600">
      Don't have an account?
      <RouterLink to="/register" class="text-primary-600 hover:text-primary-700">Sign up</RouterLink>
    </p>

    <form class="mt-6 space-y-4" @submit.prevent="handleSubmit">
      <div v-if="error" class="rounded-lg bg-red-50 p-3 text-sm text-red-700">
        {{ error }}
      </div>

      <div>
        <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
        <input
          id="username"
          v-model="form.username"
          type="text"
          required
          autocomplete="username"
          class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none"
        />
      </div>

      <div>
        <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
        <input
          id="password"
          v-model="form.password"
          type="password"
          required
          autocomplete="current-password"
          class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none"
        />
      </div>

      <button
        type="submit"
        :disabled="auth.loading"
        class="w-full rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700 disabled:opacity-50"
      >
        {{ auth.loading ? 'Logging in...' : 'Log in' }}
      </button>
    </form>
  </div>
</template>

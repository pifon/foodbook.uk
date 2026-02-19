<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const auth = useAuthStore();
const router = useRouter();

const form = ref({
  username: '',
  email: '',
  password: '',
  password_confirmation: '',
});
const errors = ref<Record<string, string>>({});

async function handleSubmit() {
  errors.value = {};
  try {
    await auth.register(form.value);
    await router.push('/dashboard');
  } catch (e: unknown) {
    const response = (e as { response?: { data?: { errors?: Array<{ source?: { pointer?: string }; detail?: string }> } } })?.response;
    if (response?.data?.errors) {
      for (const err of response.data.errors) {
        const field = err.source?.pointer?.split('/').pop() || 'general';
        errors.value[field] = err.detail || 'Validation error';
      }
    } else {
      errors.value.general = 'Registration failed. Please try again.';
    }
  }
}
</script>

<template>
  <div>
    <h2 class="text-xl font-semibold text-gray-900">Create your account</h2>
    <p class="mt-1 text-sm text-gray-600">
      Already have an account?
      <RouterLink to="/login" class="text-primary-600 hover:text-primary-700">Log in</RouterLink>
    </p>

    <form class="mt-6 space-y-4" @submit.prevent="handleSubmit">
      <div v-if="errors.general" class="rounded-lg bg-red-50 p-3 text-sm text-red-700">
        {{ errors.general }}
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
        <p v-if="errors.username" class="mt-1 text-xs text-red-600">{{ errors.username }}</p>
      </div>

      <div>
        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
        <input
          id="email"
          v-model="form.email"
          type="email"
          required
          autocomplete="email"
          class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none"
        />
        <p v-if="errors.email" class="mt-1 text-xs text-red-600">{{ errors.email }}</p>
      </div>

      <div>
        <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
        <input
          id="password"
          v-model="form.password"
          type="password"
          required
          autocomplete="new-password"
          class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none"
        />
        <p v-if="errors.password" class="mt-1 text-xs text-red-600">{{ errors.password }}</p>
      </div>

      <div>
        <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm password</label>
        <input
          id="password_confirmation"
          v-model="form.password_confirmation"
          type="password"
          required
          autocomplete="new-password"
          class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none"
        />
      </div>

      <button
        type="submit"
        :disabled="auth.loading"
        class="w-full rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700 disabled:opacity-50"
      >
        {{ auth.loading ? 'Creating account...' : 'Create account' }}
      </button>
    </form>
  </div>
</template>

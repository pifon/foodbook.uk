import { ref, computed } from 'vue';
import { defineStore } from 'pinia';
import { apiClient } from '@/api/client';
import type { User } from '@/types';

const TOKEN_KEY = 'foodbook_token';

export const useAuthStore = defineStore('auth', () => {
  const token = ref<string | null>(null);
  const user = ref<User | null>(null);
  const loading = ref(false);

  const isAuthenticated = computed(() => !!token.value);

  function initialize() {
    const stored = localStorage.getItem(TOKEN_KEY);
    if (stored) {
      token.value = stored;
      fetchUser();
    }
  }

  async function login(username: string, password: string) {
    loading.value = true;
    try {
      const { data } = await apiClient.post('/login', { username, password });
      setToken(data.token);
      await fetchUser();
    } finally {
      loading.value = false;
    }
  }

  async function register(payload: {
    username: string;
    email: string;
    password: string;
    password_confirmation: string;
  }) {
    loading.value = true;
    try {
      const { data } = await apiClient.post('/register', {
        data: {
          type: 'users',
          attributes: payload,
        },
      });
      setToken(data.token);
      await fetchUser();
    } finally {
      loading.value = false;
    }
  }

  async function fetchUser() {
    try {
      const { data } = await apiClient.get('/v1/me');
      user.value = {
        id: data.data.id,
        ...data.data.attributes,
      };
    } catch {
      logout();
    }
  }

  function setToken(t: string) {
    token.value = t;
    localStorage.setItem(TOKEN_KEY, t);
  }

  function logout() {
    token.value = null;
    user.value = null;
    localStorage.removeItem(TOKEN_KEY);
  }

  return {
    token,
    user,
    loading,
    isAuthenticated,
    initialize,
    login,
    register,
    fetchUser,
    logout,
  };
});

import axios from 'axios';
import router from '@/router';

export const apiClient = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL,
  headers: {
    Accept: 'application/vnd.api+json',
    'Content-Type': 'application/vnd.api+json',
  },
});

apiClient.interceptors.request.use((config) => {
  const token = localStorage.getItem('foodbook_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

apiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('foodbook_token');
      router.push({ name: 'login' });
    }
    return Promise.reject(error);
  },
);

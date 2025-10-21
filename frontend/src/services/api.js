import axios from 'axios';
import router from '../router';
import { useAuthStore } from '../stores/auth';

const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL ?? 'http://localhost/api',
  headers: {
    Accept: 'application/json',
  },
});

api.interceptors.request.use((config) => {
  const auth = useAuthStore();
  const token = auth.token.value;

  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }

  return config;
});

api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      const auth = useAuthStore();

      if (auth.isAuthenticated.value) {
        auth.clearAuth();
      }

      if (router.currentRoute.value.name !== 'login') {
        router.push({ name: 'login' });
      }
    }

    return Promise.reject(error);
  },
);

export default api;

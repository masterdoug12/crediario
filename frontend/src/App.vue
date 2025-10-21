<script setup>
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import api from './services/api';
import { useAuthStore } from './stores/auth';

const router = useRouter();
const auth = useAuthStore();

const isAuthenticated = computed(() => auth.isAuthenticated.value);
const userName = computed(() => auth.user.value?.nome ?? auth.user.value?.name ?? '');

const handleLogout = async () => {
  try {
    await api.post('/logout');
  } catch (error) {
    // Ignora erros de logout para não travar o fluxo
    console.error('Falha ao sair', error);
  } finally {
    auth.clearAuth();
    router.push({ name: 'login' });
  }
};
</script>

<template>
  <div>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
      <div class="container">
        <router-link class="navbar-brand fw-semibold" :to="{ name: 'clientes' }">
          Fiados - Crediário
        </router-link>

        <div class="collapse navbar-collapse show justify-content-end">
          <ul class="navbar-nav align-items-center gap-3">
            <li v-if="isAuthenticated" class="nav-item text-white">
              <span class="fw-semibold">Olá, {{ userName }}</span>
            </li>
            <li v-if="isAuthenticated" class="nav-item">
              <button class="btn btn-outline-light btn-sm" type="button" @click="handleLogout">
                Sair
              </button>
            </li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="container py-4">
      <router-view />
    </main>
  </div>
</template>

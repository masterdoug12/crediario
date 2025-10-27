<script setup>
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import api from '../services/api';
import { useAuthStore } from '../stores/auth';

const router = useRouter();
const auth = useAuthStore();

const form = reactive({
  email: 'admin@crediario.local',
  password: 'senha123',
});

const loading = ref(false);
const errorMessage = ref('');

const handleSubmit = async () => {
  errorMessage.value = '';
  loading.value = true;

  try {
    const { data } = await api.post('/login', {
      email: form.email,
      password: form.password,
      device_name: 'navegador',
    });

    auth.setAuth(data);
    router.push({ name: 'clientes' });
  } catch (error) {
    errorMessage.value =
      error.response?.data?.mensagem ?? 'Não foi possível entrar. Verifique os dados.';
  } finally {
    loading.value = false;
  }
};
</script>

<template>
  <div class="row justify-content-center py-5">
    <div class="col-md-5">
      <div class="card card-shadow border-0">
        <div class="card-body p-4">
          <h1 class="h4 text-center mb-3">Acessar o painel</h1>
          <p class="text-muted text-center mb-4">
            Utilize o usuário administrador para gerenciar os crediários.
          </p>

          <form class="vstack gap-3" @submit.prevent="handleSubmit">
            <div>
              <label class="form-label" for="email">E-mail</label>
              <input
                id="email"
                v-model="form.email"
                class="form-control"
                type="email"
                placeholder="admin@crediario.local"
                required
              />
            </div>

            <div>
              <label class="form-label" for="password">Senha</label>
              <input
                id="password"
                v-model="form.password"
                class="form-control"
                type="password"
                placeholder="••••••••"
                required
              />
            </div>

            <div v-if="errorMessage" class="alert alert-danger" role="alert">
              {{ errorMessage }}
            </div>

            <button class="btn btn-primary w-100" type="submit" :disabled="loading">
              <span v-if="loading" class="spinner-border spinner-border-sm me-2" role="status" />
              Entrar
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import dayjs from 'dayjs';
import 'dayjs/locale/pt-br';
import { onMounted, reactive, ref } from 'vue';
import api from '../services/api';

dayjs.locale('pt-br');

const loading = ref(true);
const marcacoes = ref([]);
const feedback = reactive({ type: '', message: '' });

const fetchMarcacoes = async () => {
  loading.value = true;
  feedback.type = '';
  feedback.message = '';

  try {
    const { data } = await api.get('/relatorios/marcacoes', { params: { limit: 10 } });
    marcacoes.value = Array.isArray(data?.marcacoes) ? data.marcacoes : [];
  } catch (error) {
    feedback.type = 'danger';
    feedback.message =
      error.response?.data?.mensagem ?? 'Não foi possível carregar o relatório.';
  } finally {
    loading.value = false;
  }
};

const formatDataHora = (value) => {
  if (!value) {
    return '—';
  }
  return dayjs(value).format('DD/MM/YYYY HH:mm');
};

const labelTipo = (tipo) => {
  if (tipo === 'debito') {
    return 'Débito';
  }
  if (tipo === 'pagamento') {
    return 'Pagamento';
  }
  return tipo ?? '—';
};

onMounted(() => {
  fetchMarcacoes();
});
</script>

<template>
  <div class="d-flex flex-column gap-4">
    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
      <div>
        <h1 class="h3 mb-1">Relatórios</h1>
        <p class="text-muted mb-0">Acompanhe as últimas marcações registradas.</p>
      </div>
      <div>
        <button class="btn btn-outline-primary" type="button" @click="fetchMarcacoes">
          Atualizar
        </button>
      </div>
    </div>

    <div class="card border-0 card-shadow">
      <div class="card-header bg-white border-bottom-0">
        <h2 class="h5 mb-0">Relatório de marcações</h2>
      </div>
      <div class="card-body">
        <div v-if="feedback.message" :class="`alert alert-${feedback.type}`" role="alert">
          {{ feedback.message }}
        </div>

        <div class="table-responsive">
          <table class="table align-middle mb-0">
            <thead>
              <tr>
                <th>Cliente</th>
                <th>Tipo de marcação</th>
                <th>Descrição</th>
                <th class="text-end">Data e hora</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="loading">
                <td colspan="4" class="text-center py-4">
                  <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Carregando...</span>
                  </div>
                </td>
              </tr>
              <tr v-else-if="marcacoes.length === 0">
                <td colspan="4" class="text-center py-4 text-muted">
                  Nenhuma marcação encontrada.
                </td>
              </tr>
              <tr v-for="marcacao in marcacoes" :key="`${marcacao.tipo}-${marcacao.id}`">
                <td class="fw-semibold">{{ marcacao.cliente }}</td>
                <td>{{ labelTipo(marcacao.tipo) }}</td>
                <td>{{ marcacao.descricao ?? 'Pagamento' }}</td>
                <td class="text-end fw-semibold">{{ formatDataHora(marcacao.created_at) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import dayjs from 'dayjs';
import 'dayjs/locale/pt-br';
import { onMounted, reactive, ref } from 'vue';
import api from '../services/api';

dayjs.locale('pt-br');

const loading = ref(true);
const debitos = ref([]);
const pagamentos = ref([]);
const feedback = reactive({ type: '', message: '' });

const fetchMarcacoes = async () => {
  loading.value = true;
  feedback.type = '';
  feedback.message = '';

  try {
    const { data } = await api.get('/relatorios/marcacoes', { params: { limit: 50 } });
    debitos.value = Array.isArray(data?.debitos) ? data.debitos : [];
    pagamentos.value = Array.isArray(data?.pagamentos) ? data.pagamentos : [];
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

    <div v-if="feedback.message" :class="`alert alert-${feedback.type}`" role="alert">
      {{ feedback.message }}
    </div>

    <div class="row g-4">
      <div class="col-lg-6">
        <div class="card border-0 card-shadow h-100">
          <div class="card-header bg-white border-bottom-0">
            <h2 class="h5 mb-0">Débitos</h2>
          </div>
          <div class="card-body">
            <div v-if="loading" class="text-center py-4">
              <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Carregando...</span>
              </div>
            </div>
            <div v-else-if="debitos.length === 0" class="text-center py-4 text-muted">
              Nenhum débito encontrado.
            </div>
            <div v-else class="table-responsive">
              <table class="table align-middle mb-0">
                <thead>
                  <tr>
                    <th>Cliente</th>
                    <th>Descrição</th>
                    <th class="text-end">Data e hora</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="marcacao in debitos" :key="`debito-${marcacao.id}`">
                    <td class="fw-semibold">{{ marcacao.cliente }}</td>
                    <td>{{ marcacao.descricao }}</td>
                    <td class="text-end fw-semibold">{{ formatDataHora(marcacao.created_at) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="card border-0 card-shadow h-100">
          <div class="card-header bg-white border-bottom-0">
            <h2 class="h5 mb-0">Pagamentos</h2>
          </div>
          <div class="card-body">
            <div v-if="loading" class="text-center py-4">
              <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Carregando...</span>
              </div>
            </div>
            <div v-else-if="pagamentos.length === 0" class="text-center py-4 text-muted">
              Nenhum pagamento encontrado.
            </div>
            <div v-else class="table-responsive">
              <table class="table align-middle mb-0">
                <thead>
                  <tr>
                    <th>Cliente</th>
                    <th>Descrição</th>
                    <th class="text-end">Data e hora</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="marcacao in pagamentos" :key="`pagamento-${marcacao.id}`">
                    <td class="fw-semibold">{{ marcacao.cliente }}</td>
                    <td>{{ marcacao.descricao ?? 'Pagamento' }}</td>
                    <td class="text-end fw-semibold">{{ formatDataHora(marcacao.created_at) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

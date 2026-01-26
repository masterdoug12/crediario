<script setup>
import dayjs from 'dayjs';
import 'dayjs/locale/pt-br';
import { onMounted, reactive, ref } from 'vue';
import api from '../services/api';

dayjs.locale('pt-br');

const loading = ref(true);
const debitosAtivos = ref([]);
const pagamentosAtivos = ref([]);
const debitosExcluidos = ref([]);
const pagamentosExcluidos = ref([]);
const feedback = reactive({ type: '', message: '' });
const activeTab = ref('ativos');

const currency = new Intl.NumberFormat('pt-BR', {
  style: 'currency',
  currency: 'BRL',
});

const fetchMarcacoes = async () => {
  loading.value = true;
  feedback.type = '';
  feedback.message = '';

  try {
    const { data } = await api.get('/relatorios/marcacoes', { params: { limit: 50 } });
    debitosAtivos.value = Array.isArray(data?.debitos_ativos) ? data.debitos_ativos : [];
    pagamentosAtivos.value = Array.isArray(data?.pagamentos_ativos) ? data.pagamentos_ativos : [];
    debitosExcluidos.value = Array.isArray(data?.debitos_excluidos) ? data.debitos_excluidos : [];
    pagamentosExcluidos.value = Array.isArray(data?.pagamentos_excluidos)
      ? data.pagamentos_excluidos
      : [];
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

const labelStatus = (status) => {
  if (status === 'excluido') {
    return 'Excluído';
  }
  return '—';
};

const formatValor = (value) => {
  if (value === null || value === undefined) {
    return '—';
  }
  return currency.format(Number(value));
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
        <p class="text-muted mb-0">Acompanhe as marcações registradas e excluídas.</p>
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

    <ul class="nav nav-tabs">
      <li class="nav-item">
        <button
          class="nav-link"
          :class="{ active: activeTab === 'ativos' }"
          type="button"
          @click="activeTab = 'ativos'"
        >
          Registrados
        </button>
      </li>
      <li class="nav-item">
        <button
          class="nav-link"
          :class="{ active: activeTab === 'excluidos' }"
          type="button"
          @click="activeTab = 'excluidos'"
        >
          Excluídos
        </button>
      </li>
    </ul>

    <div class="tab-content">
      <div class="tab-pane fade" :class="{ show: activeTab === 'ativos', active: activeTab === 'ativos' }">
        <div class="row g-4 mt-1">
          <div class="col-lg-6">
            <div class="card border-0 card-shadow h-100">
              <div class="card-header bg-white border-bottom-0">
                <h2 class="h5 mb-0">Débitos registrados</h2>
              </div>
              <div class="card-body">
                <div v-if="loading" class="text-center py-4">
                  <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Carregando...</span>
                  </div>
                </div>
                <div v-else-if="debitosAtivos.length === 0" class="text-center py-4 text-muted">
                  Nenhum débito registrado.
                </div>
                <div v-else class="table-responsive">
                  <table class="table align-middle mb-0">
                    <thead>
                      <tr>
                        <th>Cliente</th>
                        <th>Tipo</th>
                        <th>Descrição</th>
                        <th class="text-end">Valor</th>
                        <th class="text-end">Data e hora</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="marcacao in debitosAtivos" :key="`debito-ativo-${marcacao.id}`">
                        <td class="fw-semibold">{{ marcacao.cliente }}</td>
                        <td>{{ marcacao.categoria ?? '—' }}</td>
                        <td>{{ marcacao.descricao }}</td>
                        <td class="text-end fw-semibold">{{ formatValor(marcacao.valor) }}</td>
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
                <h2 class="h5 mb-0">Pagamentos registrados</h2>
              </div>
              <div class="card-body">
                <div v-if="loading" class="text-center py-4">
                  <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Carregando...</span>
                  </div>
                </div>
                <div v-else-if="pagamentosAtivos.length === 0" class="text-center py-4 text-muted">
                  Nenhum pagamento registrado.
                </div>
                <div v-else class="table-responsive">
                  <table class="table align-middle mb-0">
                    <thead>
                      <tr>
                        <th>Cliente</th>
                        <th>Tipo</th>
                        <th>Descrição</th>
                        <th class="text-end">Valor</th>
                        <th class="text-end">Data e hora</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr
                        v-for="marcacao in pagamentosAtivos"
                        :key="`pagamento-ativo-${marcacao.id}`"
                      >
                        <td class="fw-semibold">{{ marcacao.cliente }}</td>
                        <td>—</td>
                        <td>{{ marcacao.descricao ?? 'Pagamento' }}</td>
                        <td class="text-end fw-semibold">{{ formatValor(marcacao.valor) }}</td>
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

      <div class="tab-pane fade" :class="{ show: activeTab === 'excluidos', active: activeTab === 'excluidos' }">
        <div class="row g-4 mt-1">
          <div class="col-lg-6">
            <div class="card border-0 card-shadow h-100">
              <div class="card-header bg-white border-bottom-0">
                <h2 class="h5 mb-0">Débitos excluídos</h2>
              </div>
              <div class="card-body">
                <div v-if="loading" class="text-center py-4">
                  <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Carregando...</span>
                  </div>
                </div>
                <div v-else-if="debitosExcluidos.length === 0" class="text-center py-4 text-muted">
                  Nenhum débito excluído encontrado.
                </div>
                <div v-else class="table-responsive">
                  <table class="table align-middle mb-0">
                    <thead>
                      <tr>
                        <th>Cliente</th>
                        <th>Tipo</th>
                        <th>Descrição</th>
                        <th class="text-end">Valor</th>
                        <th class="text-end">Excluído em</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="marcacao in debitosExcluidos" :key="`debito-excluido-${marcacao.id}`">
                        <td class="fw-semibold">{{ marcacao.cliente }}</td>
                        <td>{{ marcacao.categoria ?? '—' }}</td>
                        <td>
                          <div class="fw-semibold">{{ marcacao.descricao }}</div>
                          <div class="text-muted-sm">{{ labelStatus(marcacao.status) }}</div>
                        </td>
                        <td class="text-end fw-semibold">{{ formatValor(marcacao.valor) }}</td>
                        <td class="text-end fw-semibold">{{ formatDataHora(marcacao.excluido_em) }}</td>
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
                <h2 class="h5 mb-0">Pagamentos excluídos</h2>
              </div>
              <div class="card-body">
                <div v-if="loading" class="text-center py-4">
                  <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Carregando...</span>
                  </div>
                </div>
                <div v-else-if="pagamentosExcluidos.length === 0" class="text-center py-4 text-muted">
                  Nenhum pagamento excluído encontrado.
                </div>
                <div v-else class="table-responsive">
                  <table class="table align-middle mb-0">
                    <thead>
                      <tr>
                        <th>Cliente</th>
                        <th>Tipo</th>
                        <th>Descrição</th>
                        <th class="text-end">Valor</th>
                        <th class="text-end">Excluído em</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr
                        v-for="marcacao in pagamentosExcluidos"
                        :key="`pagamento-excluido-${marcacao.id}`"
                      >
                        <td class="fw-semibold">{{ marcacao.cliente }}</td>
                        <td>—</td>
                        <td>
                          <div class="fw-semibold">{{ marcacao.descricao ?? 'Pagamento' }}</div>
                          <div class="text-muted-sm">{{ labelStatus(marcacao.status) }}</div>
                        </td>
                        <td class="text-end fw-semibold">{{ formatValor(marcacao.valor) }}</td>
                        <td class="text-end fw-semibold">{{ formatDataHora(marcacao.excluido_em) }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

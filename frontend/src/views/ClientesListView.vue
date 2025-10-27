<script setup>
import { computed, onMounted, onUnmounted, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import api from '../services/api';
import FiltroAlfabetico from '../components/FiltroAlfabetico.vue';

const router = useRouter();
const clientes = ref([]);
const loading = ref(false);
const searchTerm = ref('');
const feedback = reactive({ type: '', message: '' });

const formState = reactive({
  id: null,
  nome: '',
  telefone: '',
  endereco: '',
});

const formErrors = reactive({});
const showForm = ref(false);
const saving = ref(false);

const currency = new Intl.NumberFormat('pt-BR', {
  style: 'currency',
  currency: 'BRL',
});

let searchTimeout;
const filtroStorageKey = 'clientes-filtro-letra';
const selectedLetter = ref('Todos');
const totalDebitos = ref(0);

const totalDebitosFormatado = computed(() => currency.format(totalDebitos.value ?? 0));

const fetchClientes = async ({ search, letter } = {}) => {
  loading.value = true;
  const params = {};
  const effectiveSearch = typeof search === 'string' ? search : searchTerm.value.trim();
  const effectiveLetter = typeof letter === 'string' ? letter : selectedLetter.value;

  if (effectiveSearch) {
    params.q = effectiveSearch;
  }

  if (effectiveLetter && effectiveLetter !== 'Todos') {
    params.letra = effectiveLetter;
  }

  try {
    const { data } = await api.get('/clientes', { params });
    const listaClientes = Array.isArray(data) ? data : data?.clientes ?? [];

    clientes.value = listaClientes;

    if (Array.isArray(data)) {
      totalDebitos.value = listaClientes.reduce(
        (acumulado, cliente) => acumulado + Number(cliente.total_debitos ?? cliente.valor_total_debito ?? 0),
        0,
      );
    } else {
      totalDebitos.value = Number(data?.total_debitos ?? 0);
    }
  } catch (error) {
    feedback.type = 'danger';
    feedback.message =
      error.response?.data?.mensagem ?? 'Não foi possível carregar os clientes.';
  } finally {
    loading.value = false;
  }
};

const applySearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    fetchClientes({ search: searchTerm.value.trim() });
  }, 400);
};

const openNewForm = () => {
  formState.id = null;
  formState.nome = '';
  formState.telefone = '';
  formState.endereco = '';
  Object.keys(formErrors).forEach((key) => delete formErrors[key]);
  showForm.value = true;
};

const openEditForm = (cliente) => {
  formState.id = cliente.id;
  formState.nome = cliente.nome;
  formState.telefone = cliente.telefone ?? '';
  formState.endereco = cliente.endereco ?? '';
  Object.keys(formErrors).forEach((key) => delete formErrors[key]);
  showForm.value = true;
};

const closeForm = () => {
  showForm.value = false;
};

const saveCliente = async () => {
  saving.value = true;
  Object.keys(formErrors).forEach((key) => delete formErrors[key]);
  feedback.type = '';
  feedback.message = '';

  try {
    const payload = {
      nome: formState.nome,
      telefone: formState.telefone || null,
      endereco: formState.endereco || null,
    };

    if (formState.id) {
      await api.put(`/clientes/${formState.id}`, payload);
      feedback.type = 'success';
      feedback.message = 'Cliente atualizado com sucesso!';
    } else {
      await api.post('/clientes', payload);
      feedback.type = 'success';
      feedback.message = 'Cliente cadastrado com sucesso!';
    }

    await fetchClientes();
    showForm.value = false;
  } catch (error) {
    if (error.response?.status === 422 && error.response.data.errors) {
      Object.assign(formErrors, error.response.data.errors);
    } else {
      feedback.type = 'danger';
      feedback.message =
        error.response?.data?.mensagem ?? 'Erro ao salvar cliente. Tente novamente.';
    }
  } finally {
    saving.value = false;
  }
};

const deleteCliente = async (cliente) => {
  if (!confirm(`Deseja realmente excluir o cliente ${cliente.nome}?`)) {
    return;
  }

  try {
    await api.delete(`/clientes/${cliente.id}`);
    feedback.type = 'success';
    feedback.message = 'Cliente removido com sucesso.';
    await fetchClientes();
  } catch (error) {
    feedback.type = 'danger';
    feedback.message = error.response?.data?.mensagem ?? 'Não foi possível excluir o cliente.';
  }
};

const irParaDetalhes = (cliente) => {
  router.push({ name: 'cliente-detalhes', params: { id: cliente.id } });
};

onMounted(() => {
  if (typeof window !== 'undefined') {
    const storedLetter = window.localStorage.getItem(filtroStorageKey);
    if (storedLetter) {
      selectedLetter.value = storedLetter;
    }
  }

  fetchClientes();
});

onUnmounted(() => {
  clearTimeout(searchTimeout);
});

const handleSelectLetter = (letter) => {
  selectedLetter.value = letter;

  if (typeof window !== 'undefined') {
    if (letter === 'Todos') {
      window.localStorage.removeItem(filtroStorageKey);
    } else {
      window.localStorage.setItem(filtroStorageKey, letter);
    }
  }

  fetchClientes({ letter });
};
</script>

<template>
  <div class="d-flex flex-column gap-4">
    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
      <div>
        <h1 class="h3 mb-1">Clientes</h1>
        <p class="text-muted mb-0">Gerencie os crediários e acompanhe os saldos em tempo real.</p>
      </div>
      <div>
        <button class="btn btn-primary" type="button" @click="openNewForm">
          Novo cliente
        </button>
      </div>
    </div>

    <div class="card border-0 card-shadow">
      <div class="card-body">
        <div class="row g-2 mb-4 align-items-center">
          <div class="col-md-6">
            <label class="form-label text-muted-sm" for="busca">Buscar por nome ou telefone</label>
            <input
              id="busca"
              v-model="searchTerm"
              class="form-control"
              type="search"
              placeholder="Digite para filtrar"
              @input="applySearch"
            />
          </div>
        </div>

        <FiltroAlfabetico
          class="mb-3"
          :selected-letter="selectedLetter"
          @select="handleSelectLetter"
        />

        <div class="d-flex justify-content-end mb-3">
          <span class="fw-semibold">Total de débitos: {{ totalDebitosFormatado }}</span>
        </div>

        <div v-if="feedback.message" :class="`alert alert-${feedback.type}`" role="alert">
          {{ feedback.message }}
        </div>

        <div class="table-responsive">
          <table class="table align-middle">
            <thead>
              <tr>
                <th>Cliente</th>
                <th>Telefone</th>
                <th>Endereço</th>
                <th class="text-end">Saldo atual</th>
                <th class="text-end">Ações</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="loading">
                <td colspan="5" class="text-center py-4">
                  <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Carregando...</span>
                  </div>
                </td>
              </tr>
              <tr v-else-if="clientes.length === 0">
                <td colspan="5" class="text-center py-4 text-muted">Nenhum cliente encontrado.</td>
              </tr>
              <tr v-for="cliente in clientes" :key="cliente.id">
                <td class="fw-semibold">{{ cliente.nome }}</td>
                <td>{{ cliente.telefone ?? '—' }}</td>
                <td>{{ cliente.endereco ?? '—' }}</td>
                <td class="text-end fw-semibold" :class="cliente.saldo_atual > 0 ? 'text-danger' : 'text-success'">
                  {{ currency.format(cliente.saldo_atual) }}
                </td>
                <td class="text-end">
                  <div class="btn-group btn-group-sm" role="group">
                    <button class="btn btn-outline-secondary" type="button" @click="irParaDetalhes(cliente)">
                      Detalhes
                    </button>
                    <button class="btn btn-outline-secondary" type="button" @click="openEditForm(cliente)">
                      Editar
                    </button>
                    <button class="btn btn-outline-danger" type="button" @click="deleteCliente(cliente)">
                      Excluir
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div v-if="showForm" class="modal fade show d-block" tabindex="-1" role="dialog" aria-modal="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h2 class="modal-title h5 mb-0">
              {{ formState.id ? 'Editar cliente' : 'Novo cliente' }}
            </h2>
            <button type="button" class="btn-close" aria-label="Fechar" @click="closeForm" />
          </div>
          <form @submit.prevent="saveCliente">
            <div class="modal-body vstack gap-3">
              <div>
                <label class="form-label" for="nome">Nome completo</label>
                <input
                  id="nome"
                  v-model="formState.nome"
                  class="form-control"
                  type="text"
                  required
                />
                <div v-if="formErrors.nome" class="text-danger small mt-1">
                  {{ formErrors.nome[0] }}
                </div>
              </div>
              <div>
                <label class="form-label" for="telefone">Telefone</label>
                <input id="telefone" v-model="formState.telefone" class="form-control" type="text" />
                <div v-if="formErrors.telefone" class="text-danger small mt-1">
                  {{ formErrors.telefone[0] }}
                </div>
              </div>
              <div>
                <label class="form-label" for="endereco">Endereço</label>
                <input id="endereco" v-model="formState.endereco" class="form-control" type="text" />
                <div v-if="formErrors.endereco" class="text-danger small mt-1">
                  {{ formErrors.endereco[0] }}
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" @click="closeForm">Cancelar</button>
              <button class="btn btn-primary" type="submit" :disabled="saving">
                <span v-if="saving" class="spinner-border spinner-border-sm me-2" role="status" />
                Salvar
              </button>
            </div>
          </form>
        </div>
      </div>

    </div>
  </div>
</template>

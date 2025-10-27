<script setup>
import dayjs from 'dayjs';
import 'dayjs/locale/pt-br';
import { computed, onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../services/api';

dayjs.locale('pt-br');

const props = defineProps({
  id: {
    type: [String, Number],
    required: true,
  },
});

const router = useRouter();
const route = useRoute();

const cliente = ref(null);
const movimentos = ref([]);
const loading = ref(true);
const errorMessage = ref('');
const feedback = reactive({ type: '', message: '' });

const currency = new Intl.NumberFormat('pt-BR', {
  style: 'currency',
  currency: 'BRL',
});

const categoriasDebito = ['Ferragens', 'PET'];

const debitoForm = reactive({
  descricao: '',
  tipo: categoriasDebito[0],
  valor: '',
  data: dayjs().format('YYYY-MM-DD'),
});

const pagamentoForm = reactive({
  descricao: '',
  valor: '',
  data: dayjs().format('YYYY-MM-DD'),
});

const debitoErrors = reactive({});
const pagamentoErrors = reactive({});

const savingDebito = ref(false);
const savingPagamento = ref(false);

const showDebitoModal = ref(false);
const showPagamentoModal = ref(false);
const removendoMovimento = ref(null);

const fetchCliente = async () => {
  loading.value = true;
  errorMessage.value = '';
  feedback.type = '';
  feedback.message = '';

  try {
    const [clienteResp, movimentosResp] = await Promise.all([
      api.get(`/clientes/${props.id}`),
      api.get(`/clientes/${props.id}/movimentos`),
    ]);

    cliente.value = clienteResp.data;
    movimentos.value = movimentosResp.data.movimentos;
  } catch (error) {
    errorMessage.value =
      error.response?.data?.mensagem ?? 'Não foi possível carregar os dados do cliente.';
  } finally {
    loading.value = false;
  }
};

const resetFeedbacks = () => {
  feedback.type = '';
  feedback.message = '';
};

const openDebitoModal = () => {
  resetFeedbacks();
  Object.keys(debitoErrors).forEach((key) => delete debitoErrors[key]);
  debitoForm.descricao = '';
  debitoForm.tipo = categoriasDebito[0];
  debitoForm.valor = '';
  debitoForm.data = dayjs().format('YYYY-MM-DD');
  showDebitoModal.value = true;
};

const openPagamentoModal = () => {
  resetFeedbacks();
  Object.keys(pagamentoErrors).forEach((key) => delete pagamentoErrors[key]);
  pagamentoForm.descricao = '';
  pagamentoForm.valor = '';
  pagamentoForm.data = dayjs().format('YYYY-MM-DD');
  showPagamentoModal.value = true;
};

const closeDebitoModal = () => {
  showDebitoModal.value = false;
};

const closePagamentoModal = () => {
  showPagamentoModal.value = false;
};

const salvarDebito = async () => {
  savingDebito.value = true;
  Object.keys(debitoErrors).forEach((key) => delete debitoErrors[key]);
  resetFeedbacks();

  try {
    await api.post(`/clientes/${props.id}/debito`, {
      descricao: debitoForm.descricao,
      tipo: debitoForm.tipo,
      valor: debitoForm.valor,
      data: debitoForm.data,
    });

    feedback.type = 'success';
    feedback.message = 'Débito registrado com sucesso!';
    showDebitoModal.value = false;
    await fetchCliente();
  } catch (error) {
    if (error.response?.status === 422 && error.response.data.errors) {
      Object.assign(debitoErrors, error.response.data.errors);
    } else {
      feedback.type = 'danger';
      feedback.message = error.response?.data?.mensagem ?? 'Erro ao registrar o débito.';
    }
  } finally {
    savingDebito.value = false;
  }
};

const salvarPagamento = async () => {
  savingPagamento.value = true;
  Object.keys(pagamentoErrors).forEach((key) => delete pagamentoErrors[key]);
  resetFeedbacks();

  try {
    await api.post(`/clientes/${props.id}/pagamento`, {
      descricao: pagamentoForm.descricao || null,
      valor: pagamentoForm.valor,
      data: pagamentoForm.data,
    });

    feedback.type = 'success';
    feedback.message = 'Pagamento registrado com sucesso!';
    showPagamentoModal.value = false;
    await fetchCliente();
  } catch (error) {
    if (error.response?.status === 422 && error.response.data.errors) {
      Object.assign(pagamentoErrors, error.response.data.errors);
    } else {
      feedback.type = 'danger';
      feedback.message = error.response?.data?.mensagem ?? 'Erro ao registrar o pagamento.';
    }
  } finally {
    savingPagamento.value = false;
  }
};

const formatDate = (value) => dayjs(value).format('DD/MM/YYYY');
const debitosPorCategoria = computed(() => {
  const categoriasPrincipais = categoriasDebito.map((categoria) => ({
    categoria,
    itens: [],
    total: 0,
  }));

  if (!cliente.value?.debitos?.length) {
    return categoriasPrincipais;
  }

  const mapa = Object.fromEntries(
    categoriasPrincipais.map((grupo) => [grupo.categoria.toLowerCase(), grupo]),
  );
  const extras = {
    categoria: 'Outros',
    itens: [],
    total: 0,
  };

  cliente.value.debitos.forEach((debito) => {
    const chave = (debito.tipo ?? '').toString().toLowerCase();
    const grupo = mapa[chave];

    if (!grupo) {
      extras.itens.push(debito);
      extras.total += Number(debito.valor ?? 0);
      return;
    }

    grupo.itens.push(debito);
    grupo.total += Number(debito.valor ?? 0);
  });

  return extras.itens.length > 0 ? [...categoriasPrincipais, extras] : categoriasPrincipais;
});

const voltarLista = () => {
  router.push({ name: 'clientes', query: route.query });
};

const excluirMovimento = async (movimento) => {
  if (
    !confirm(
      `Deseja realmente excluir este ${movimento.tipo_movimento === 'debito' ? 'débito' : 'pagamento'}?`,
    )
  ) {
    return;
  }

  removendoMovimento.value = `${movimento.tipo_movimento}-${movimento.id}`;
  resetFeedbacks();

  try {
    if (movimento.tipo_movimento === 'debito') {
      await api.delete(`/clientes/${props.id}/debitos/${movimento.id}`);
    } else {
      await api.delete(`/clientes/${props.id}/pagamentos/${movimento.id}`);
    }

    feedback.type = 'success';
    feedback.message = 'Movimento removido com sucesso.';
    await fetchCliente();
  } catch (error) {
    feedback.type = 'danger';
    feedback.message =
      error.response?.data?.mensagem ?? 'Não foi possível remover o movimento.';
  } finally {
    removendoMovimento.value = null;
  }
};

onMounted(() => {
  fetchCliente();
});
</script>

<template>
  <div>
    <button class="btn btn-link text-decoration-none ps-0 mb-3" type="button" @click="voltarLista">
      ← Voltar para clientes
    </button>

    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Carregando...</span>
      </div>
    </div>

    <div v-else-if="errorMessage" class="alert alert-danger" role="alert">
      {{ errorMessage }}
    </div>

    <div v-else-if="cliente" class="vstack gap-4">
      <div class="card border-0 card-shadow">
        <div class="card-body">
          <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
            <div>
              <h1 class="h4 mb-1">{{ cliente.nome }}</h1>
              <div class="text-muted-sm">
                <span class="me-3">Telefone: {{ cliente.telefone ?? 'Não informado' }}</span>
                <span>Endereço: {{ cliente.endereco ?? 'Não informado' }}</span>
              </div>
            </div>
            <div class="text-lg-end">
              <span class="fw-semibold d-block text-muted-sm">Saldo atual</span>
              <span
                class="display-6"
                :class="cliente.saldo_atual > 0 ? 'text-danger' : 'text-success'"
              >
                {{ currency.format(cliente.saldo_atual) }}
              </span>
            </div>
          </div>

          <div class="d-flex flex-wrap gap-2 mt-4">
            <button class="btn btn-primary" type="button" @click="openDebitoModal">
              Adicionar débito
            </button>
            <button class="btn btn-outline-primary" type="button" @click="openPagamentoModal">
              Registrar pagamento
            </button>
          </div>
        </div>
      </div>

      <div v-if="feedback.message" :class="`alert alert-${feedback.type}`" role="alert">
        {{ feedback.message }}
      </div>

      <div class="card border-0 card-shadow">
        <div class="card-header bg-white border-bottom-0">
          <h2 class="h5 mb-0">Débitos por categoria</h2>
        </div>
        <div class="card-body">
          <div class="row g-4">
            <div v-for="grupo in debitosPorCategoria" :key="grupo.categoria" class="col-md-6">
              <div class="border rounded-3 p-3 h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <h3 class="h6 mb-0">{{ grupo.categoria }}</h3>
                  <span class="fw-semibold text-danger">{{ currency.format(grupo.total) }}</span>
                </div>
                <ul class="list-unstyled mb-0">
                  <li v-if="grupo.itens.length === 0" class="text-muted-sm">
                    Nenhum débito nesta categoria.
                  </li>
                  <li
                    v-for="debito in grupo.itens"
                    :key="debito.id"
                    class="d-flex justify-content-between align-items-center py-1 border-bottom"
                  >
                    <div>
                      <div class="fw-semibold">{{ debito.descricao }}</div>
                      <div class="text-muted-sm">{{ formatDate(debito.data) }}</div>
                    </div>
                    <span class="text-danger fw-semibold">
                      {{ currency.format(debito.valor) }}
                    </span>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="card border-0 card-shadow">
        <div class="card-header bg-white border-bottom-0">
          <h2 class="h5 mb-0">Linha do tempo de movimentos</h2>
        </div>
        <div class="card-body">
          <ul class="list-group list-group-flush">
            <li v-if="movimentos.length === 0" class="list-group-item text-muted">
              Nenhum movimento cadastrado.
            </li>
            <li
              v-for="movimento in movimentos"
              :key="`${movimento.tipo_movimento}-${movimento.id}`"
              class="list-group-item d-flex justify-content-between align-items-center gap-3 flex-wrap"
            >
              <div class="me-auto">
                <div class="fw-semibold">
                  {{
                    movimento.tipo_movimento === 'debito'
                      ? movimento.descricao
                      : movimento.descricao ?? 'Pagamento'
                  }}
                </div>
                <div class="text-muted-sm">
                  {{ formatDate(movimento.data) }}
                  <span v-if="movimento.tipo_movimento === 'debito' && movimento.categoria" class="ms-2">
                    · {{ movimento.categoria }}
                  </span>
                </div>
              </div>
              <span
                :class="[
                  'badge fw-semibold',
                  movimento.tipo_movimento === 'debito'
                    ? 'bg-danger-subtle text-danger'
                    : 'bg-success-subtle text-success',
                ]"
              >
                {{ currency.format(movimento.valor) }}
              </span>
              <button
                class="btn btn-sm btn-outline-danger"
                type="button"
                :disabled="removendoMovimento === `${movimento.tipo_movimento}-${movimento.id}`"
                @click="excluirMovimento(movimento)"
              >
                <span
                  v-if="removendoMovimento === `${movimento.tipo_movimento}-${movimento.id}`"
                  class="spinner-border spinner-border-sm me-2"
                  role="status"
                />
                Excluir
              </button>
            </li>
          </ul>
        </div>
      </div>
    </div>

    <div v-if="showDebitoModal" class="modal fade show d-block" tabindex="-1" role="dialog" aria-modal="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h2 class="h5 mb-0">Adicionar débito</h2>
            <button type="button" class="btn-close" aria-label="Fechar" @click="closeDebitoModal" />
          </div>
          <form @submit.prevent="salvarDebito">
            <div class="modal-body vstack gap-3">
              <div>
                <label class="form-label" for="descricaoDebito">Descrição</label>
                <input
                  id="descricaoDebito"
                  v-model="debitoForm.descricao"
                  class="form-control"
                  type="text"
                  required
                />
                <div v-if="debitoErrors.descricao" class="text-danger small mt-1">
                  {{ debitoErrors.descricao[0] }}
                </div>
              </div>
              <div>
                <label class="form-label" for="tipoDebito">Tipo ou categoria</label>
                <select id="tipoDebito" v-model="debitoForm.tipo" class="form-select" required>
                  <option v-for="categoria in categoriasDebito" :key="categoria" :value="categoria">
                    {{ categoria }}
                  </option>
                </select>
                <div v-if="debitoErrors.tipo" class="text-danger small mt-1">
                  {{ debitoErrors.tipo[0] }}
                </div>
              </div>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label" for="valorDebito">Valor</label>
                  <input
                    id="valorDebito"
                    v-model.number="debitoForm.valor"
                    class="form-control"
                    type="number"
                    min="0"
                    step="0.01"
                    required
                  />
                  <div v-if="debitoErrors.valor" class="text-danger small mt-1">
                    {{ debitoErrors.valor[0] }}
                  </div>
                </div>
                <div class="col-md-6">
                  <label class="form-label" for="dataDebito">Data</label>
                  <input
                    id="dataDebito"
                    v-model="debitoForm.data"
                    class="form-control"
                    type="date"
                    required
                  />
                  <div v-if="debitoErrors.data" class="text-danger small mt-1">
                    {{ debitoErrors.data[0] }}
                  </div>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" @click="closeDebitoModal">Cancelar</button>
              <button class="btn btn-primary" type="submit" :disabled="savingDebito">
                <span v-if="savingDebito" class="spinner-border spinner-border-sm me-2" role="status" />
                Salvar débito
              </button>
            </div>
          </form>
        </div>
      </div>

    </div>

    <div
      v-if="showPagamentoModal"
      class="modal fade show d-block"
      tabindex="-1"
      role="dialog"
      aria-modal="true"
    >
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h2 class="h5 mb-0">Registrar pagamento</h2>
            <button type="button" class="btn-close" aria-label="Fechar" @click="closePagamentoModal" />
          </div>
          <form @submit.prevent="salvarPagamento">
            <div class="modal-body vstack gap-3">
              <div>
                <label class="form-label" for="descricaoPagamento">Descrição</label>
                <input
                  id="descricaoPagamento"
                  v-model="pagamentoForm.descricao"
                  class="form-control"
                  type="text"
                  placeholder="Opcional"
                />
                <div v-if="pagamentoErrors.descricao" class="text-danger small mt-1">
                  {{ pagamentoErrors.descricao[0] }}
                </div>
              </div>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label" for="valorPagamento">Valor</label>
                  <input
                    id="valorPagamento"
                    v-model.number="pagamentoForm.valor"
                    class="form-control"
                    type="number"
                    min="0"
                    step="0.01"
                    required
                  />
                  <div v-if="pagamentoErrors.valor" class="text-danger small mt-1">
                    {{ pagamentoErrors.valor[0] }}
                  </div>
                </div>
                <div class="col-md-6">
                  <label class="form-label" for="dataPagamento">Data</label>
                  <input
                    id="dataPagamento"
                    v-model="pagamentoForm.data"
                    class="form-control"
                    type="date"
                    required
                  />
                  <div v-if="pagamentoErrors.data" class="text-danger small mt-1">
                    {{ pagamentoErrors.data[0] }}
                  </div>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" @click="closePagamentoModal">
                Cancelar
              </button>
              <button class="btn btn-primary" type="submit" :disabled="savingPagamento">
                <span v-if="savingPagamento" class="spinner-border spinner-border-sm me-2" role="status" />
                Salvar pagamento
              </button>
            </div>
          </form>
        </div>
      </div>

    </div>
  </div>
</template>

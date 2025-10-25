import { computed, reactive } from 'vue';

const STORAGE_TOKEN_KEY = 'crediario_token';
const STORAGE_USER_KEY = 'crediario_user';

const state = reactive({
  token: localStorage.getItem(STORAGE_TOKEN_KEY) ?? '',
  user: '',//JSON.parse(localStorage.getItem(STORAGE_USER_KEY) || "null"),
  

});

function setAuth({ token, usuario }) {
  state.token = token;
  state.user = usuario;

  localStorage.setItem(STORAGE_TOKEN_KEY, token);
  localStorage.setItem(STORAGE_USER_KEY, JSON.stringify(usuario));
}

function clearAuth() {
  state.token = '';
  state.user = null;

  localStorage.removeItem(STORAGE_TOKEN_KEY);
  localStorage.removeItem(STORAGE_USER_KEY);
}

const store = {
  token: computed(() => state.token),
  user: computed(() => state.user),
  isAuthenticated: computed(() => Boolean(state.token)),
  setAuth,
  clearAuth,
};

export function useAuthStore() {
  return store;
}

import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const LoginView = () => import('../views/LoginView.vue');
const ClientesListView = () => import('../views/ClientesListView.vue');
const ClienteDetalheView = () => import('../views/ClienteDetalheView.vue');

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/',
      redirect: { name: 'clientes' },
    },
    {
      path: '/login',
      name: 'login',
      component: LoginView,
      meta: { public: true },
    },
    {
      path: '/clientes',
      name: 'clientes',
      component: ClientesListView,
      meta: { requiresAuth: true },
    },
    {
      path: '/clientes/:id',
      name: 'cliente-detalhes',
      component: ClienteDetalheView,
      props: true,
      meta: { requiresAuth: true },
    },
  ],
});

router.beforeEach((to, from, next) => {
  const auth = useAuthStore();

  if (to.meta.requiresAuth && !auth.isAuthenticated.value) {
    return next({ name: 'login' });
  }

  if (to.name === 'login' && auth.isAuthenticated.value) {
    return next({ name: 'clientes' });
  }

  next();
});

export default router;

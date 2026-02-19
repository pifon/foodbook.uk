import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      component: () => import('@/layouts/DefaultLayout.vue'),
      children: [
        {
          path: '',
          name: 'home',
          component: () => import('@/pages/Home.vue'),
        },
        {
          path: 'recipes',
          name: 'recipes',
          component: () => import('@/pages/recipes/RecipeIndex.vue'),
        },
        {
          path: 'recipes/:slug',
          name: 'recipe',
          component: () => import('@/pages/recipes/RecipeShow.vue'),
          props: true,
        },
      ],
    },
    {
      path: '/',
      component: () => import('@/layouts/DefaultLayout.vue'),
      meta: { requiresAuth: true },
      children: [
        {
          path: 'dashboard',
          name: 'dashboard',
          component: () => import('@/pages/Dashboard.vue'),
        },
        {
          path: 'collections',
          name: 'collections',
          component: () => import('@/pages/collections/CollectionIndex.vue'),
        },
        {
          path: 'collections/:id',
          name: 'collection',
          component: () => import('@/pages/collections/CollectionShow.vue'),
          props: true,
        },
        {
          path: 'shopping-lists',
          name: 'shopping-lists',
          component: () => import('@/pages/shopping/ShoppingListIndex.vue'),
        },
        {
          path: 'shopping-lists/:id',
          name: 'shopping-list',
          component: () => import('@/pages/shopping/ShoppingListShow.vue'),
          props: true,
        },
        {
          path: 'pantry',
          name: 'pantry',
          component: () => import('@/pages/pantry/PantryIndex.vue'),
        },
        {
          path: 'settings',
          name: 'settings',
          component: () => import('@/pages/settings/Settings.vue'),
        },
      ],
    },
    {
      path: '/',
      component: () => import('@/layouts/AuthLayout.vue'),
      meta: { guestOnly: true },
      children: [
        {
          path: 'login',
          name: 'login',
          component: () => import('@/pages/auth/Login.vue'),
        },
        {
          path: 'register',
          name: 'register',
          component: () => import('@/pages/auth/Register.vue'),
        },
      ],
    },
  ],
});

router.beforeEach((to) => {
  const auth = useAuthStore();

  if (to.matched.some((r) => r.meta.requiresAuth) && !auth.isAuthenticated) {
    return { name: 'login', query: { redirect: to.fullPath } };
  }

  if (to.matched.some((r) => r.meta.guestOnly) && auth.isAuthenticated) {
    return { name: 'dashboard' };
  }
});

export default router;

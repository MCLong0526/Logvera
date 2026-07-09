import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const routes = [
  { path: '/login', name: 'login', component: () => import('../views/Login.vue'), meta: { public: true } },
  {
    path: '/',
    component: () => import('../layouts/AppLayout.vue'),
    children: [
      { path: '', name: 'dashboard', component: () => import('../views/Dashboard.vue') },
      { path: 'projects', name: 'projects', component: () => import('../views/Projects.vue') },
      { path: 'projects/:id', name: 'project', component: () => import('../views/ProjectDetail.vue') },
      { path: 'tasks/:id', name: 'task', component: () => import('../views/TaskDetail.vue') },
      { path: 'files', name: 'files', component: () => import('../views/Files.vue') },
      { path: 'users', name: 'users', component: () => import('../views/Users.vue') },
      { path: 'profile', name: 'profile', component: () => import('../views/Profile.vue') },
    ],
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach((to) => {
  const auth = useAuthStore()
  if (!to.meta.public && !auth.isAuthenticated) return { name: 'login' }
  if (to.name === 'login' && auth.isAuthenticated) return { name: 'dashboard' }
})

export default router

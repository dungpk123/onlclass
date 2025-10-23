import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

// Import views
import Login from '@/views/Login.vue'
import Register from '@/views/Register.vue'
import Dashboard from '@/views/Dashboard.vue'
import Profile from '@/views/Profile.vue'

// Admin views
import UserManagement from '@/views/admin/UserManagement.vue'

// Class views
import ClassList from '@/views/classes/ClassList.vue'
import ClassDetail from '@/views/classes/ClassDetail.vue'
import CreateClass from '@/views/classes/CreateClass.vue'
import EditClass from '@/views/classes/EditClass.vue'

// Document views
import DocumentList from '@/views/documents/DocumentList.vue'

// Evaluation views
import EvaluationList from '@/views/evaluations/EvaluationList.vue'
import CreateEvaluation from '@/views/evaluations/CreateEvaluation.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      redirect: '/dashboard'
    },
    {
      path: '/login',
      name: 'login',
      component: Login,
      meta: { guest: true }
    },
    {
      path: '/register',
      name: 'register', 
      component: Register,
      meta: { guest: true }
    },
    {
      path: '/dashboard',
      name: 'dashboard',
      component: Dashboard,
      meta: { requiresAuth: true }
    },
    {
      path: '/profile',
      name: 'profile',
      component: Profile,
      meta: { requiresAuth: true }
    },
    {
      path: '/users',
      name: 'users',
      component: UserManagement,
      meta: { requiresAuth: true, requiresAdmin: true }
    },
    {
      path: '/classes',
      name: 'classes',
      component: ClassList,
      meta: { requiresAuth: true }
    },
    {
      path: '/classes/create',
      name: 'create-class',
      component: CreateClass,
      meta: { requiresAuth: true }
    },
    {
      path: '/classes/:id',
      name: 'class-detail',
      component: ClassDetail,
      meta: { requiresAuth: true }
    },
    {
      path: '/classes/:id/edit',
      name: 'edit-class',
      component: EditClass,
      meta: { requiresAuth: true }
    },
    {
      path: '/documents',
      name: 'documents',
      component: DocumentList,
      meta: { requiresAuth: true }
    },
    {
      path: '/evaluations',
      name: 'evaluations',
      component: EvaluationList,
      meta: { requiresAuth: true }
    },
    {
      path: '/evaluations/create',
      name: 'create-evaluation',
      component: CreateEvaluation,
      meta: { requiresAuth: true }
    }
  ]
})

// Navigation guards
router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()
  
  // Wait for auth initialization if not done yet
  if (!authStore.initialized) {
    await authStore.init()
  }
  
  if (to.meta.guest && authStore.isAuthenticated) {
    next('/dashboard')
    return
  }
  
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next('/login')
    return
  }
  
  if (to.meta.requiresAdmin && !authStore.isAdmin) {
    next('/dashboard')
    return
  }
  
  next()
})

export default router
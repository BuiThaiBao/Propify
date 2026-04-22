import { createRouter, createWebHistory } from 'vue-router'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { useAuthStore } from '@/stores/auth'

/**
 * Promise resolved sau khi initAuth() hoàn thành.
 * Guard sẽ await promise này trước khi kiểm tra auth state,
 * tránh race condition khi reload trang.
 */
let authReadyResolve
export const authReady = new Promise((resolve) => {
  authReadyResolve = resolve
})
export function resolveAuthReady() {
  authReadyResolve()
}

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: () => import('@/pages/LoginPage.vue'),
    meta: { requiresGuest: true },
  },
  {
    path: '/',
    component: AdminLayout,
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        name: 'Dashboard',
        component: () => import('@/pages/DashboardPage.vue'),
      },
      {
        path: 'posts',
        name: 'Posts',
        component: () => import('@/pages/PostsPage.vue'),
      },
      {
        path: 'packages',
        name: 'Packages',
        component: () => import('@/pages/PackagesPage.vue'),
      },
      {
        path: 'packages/create',
        name: 'PackageCreate',
        component: () => import('@/pages/PackageCreatePage.vue'),
      },
      {
        path: 'packages/:id/edit',
        name: 'PackageEdit',
        component: () => import('@/pages/PackageEditPage.vue'),
      },
      {
        path: 'users',
        name: 'Users',
        component: () => import('@/pages/UsersPage.vue'),
      },
      {
        path: 'utilities',
        name: 'Utilities',
        component: () => import('@/pages/UtilitiesPage.vue'),
      },
      {
        path: 'revenue',
        name: 'Revenue',
        component: () => import('@/pages/RevenuePage.vue'),
      },
    ],
  },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
})

/**
 * Global navigation guard.
 * - Await authReady trước — đảm bảo initAuth() đã chạy xong khi reload
 * - requiresAuth: chỉ cho phép admin đã đăng nhập truy cập
 * - requiresGuest: redirect về Dashboard nếu đã đăng nhập rồi
 */
router.beforeEach(async (to) => {
  // Chờ initAuth() hoàn thành trước khi kiểm tra (xử lý trường hợp reload)
  await authReady

  const auth = useAuthStore()

  if (to.meta.requiresAuth) {
    // Chưa có token → về Login
    if (!auth.isAuthenticated) {
      return { name: 'Login' }
    }
    // Có token nhưng không phải admin (edge case sau initAuth) → về Login
    if (auth.user && !auth.isAdmin) {
      auth.clearAuth()
      return { name: 'Login' }
    }
  }

  if (to.meta.requiresGuest && auth.isAuthenticated) {
    return { name: 'Dashboard' }
  }
})

export default router

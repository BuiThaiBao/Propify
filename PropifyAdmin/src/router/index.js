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
    component: () => import('@/pages/Auth/Login.vue'),
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
        component: () => import('@/pages/Dashboard/Page.vue'),
      },
      {
        path: 'posts',
        name: 'Posts',
        component: () => import('@/pages/Posts/index.vue'),
      },
      {
        path: 'packages',
        name: 'Packages',
        component: () => import('@/pages/Packages/ListPage.vue'),
      },
      {
        path: 'packages/create',
        name: 'PackageCreate',
        component: () => import('@/pages/Packages/CreatePage.vue'),
      },
      {
        path: 'packages/:id',
        name: 'PackageDetail',
        component: () => import('@/pages/Packages/DetailPage.vue'),
      },
      {
        path: 'packages/:id/edit',
        name: 'PackageEdit',
        component: () => import('@/pages/Packages/EditPage.vue'),
      },
      {
        path: 'users',
        name: 'Users',
        component: () => import('@/pages/Users/Page.vue'),
      },
      {
        path: 'utilities',
        name: 'Utilities',
        component: () => import('@/pages/Utilities/Page.vue'),
      },
      {
        path: 'revenue',
        name: 'Revenue',
        component: () => import('@/pages/Revenue/Page.vue'),
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

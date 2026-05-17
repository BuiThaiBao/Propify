import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { VueQueryPlugin, QueryClient } from '@tanstack/vue-query'
import App from './App.vue'
import router, { resolveAuthReady } from './router'
import './style.css'

const app = createApp(App)
const pinia = createPinia()
const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 60 * 1000,
      gcTime: 10 * 60 * 1000,
      refetchOnWindowFocus: true,
      refetchOnReconnect: true,
      retry: 1,
    },
    mutations: {
      retry: 0,
    },
  },
})

app.use(pinia)
app.use(VueQueryPlugin, { queryClient })
app.use(router)

// Khởi tạo auth state trước khi mount.
// resolveAuthReady() phải được gọi SAU initAuth() để router guard
// biết auth đã sẵn sàng và không redirect nhầm khi reload trang.
import('@/stores/auth').then(({ useAuthStore }) => {
  const auth = useAuthStore()
  auth.initAuth().finally(() => {
    resolveAuthReady() // ← Báo cho router guard: auth đã kiểm tra xong
    app.mount('#app')
  })
})

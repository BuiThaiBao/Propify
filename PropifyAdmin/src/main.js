import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router, { resolveAuthReady } from './router'
import './style.css'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
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

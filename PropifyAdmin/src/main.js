import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import './style.css'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)

// Khởi tạo auth state trước khi mount để route guard hoạt động đúng.
// Import trực tiếp store (không dùng composable) vì app chưa mounted.
import('@/stores/auth').then(({ useAuthStore }) => {
  const auth = useAuthStore()
  auth.initAuth().finally(() => {
    app.mount('#app')
  })
})

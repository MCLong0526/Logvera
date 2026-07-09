import { createApp } from 'vue'
import { createPinia } from 'pinia'
import './style.css'
import App from './App.vue'
import router from './router'
import { useAuthStore } from './stores/auth'

const app = createApp(App)
app.use(createPinia())

// Restore the session (if a token exists) before mounting.
const auth = useAuthStore()
const boot = auth.token ? auth.fetchUser().catch(() => auth.logout()) : Promise.resolve()

boot.finally(() => {
  app.use(router)
  app.mount('#app')
})

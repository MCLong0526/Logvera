import { createApp } from 'vue'
import { createPinia } from 'pinia'
// Self-hosted type — Hanken Grotesk (UI) + JetBrains Mono (numeric / metadata).
import '@fontsource/hanken-grotesk/400.css'
import '@fontsource/hanken-grotesk/500.css'
import '@fontsource/hanken-grotesk/600.css'
import '@fontsource/hanken-grotesk/700.css'
import '@fontsource/hanken-grotesk/800.css'
import '@fontsource/jetbrains-mono/400.css'
import '@fontsource/jetbrains-mono/500.css'
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

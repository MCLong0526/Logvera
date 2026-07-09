import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

// Proxy API + storage to the Laravel backend so the SPA can use relative URLs (no CORS).
export default defineConfig({
  plugins: [vue()],
  server: {
    proxy: {
      '/api': 'http://127.0.0.1:8000',
      '/storage': 'http://127.0.0.1:8000',
    },
  },
})

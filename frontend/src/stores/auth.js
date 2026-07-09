import { defineStore } from 'pinia'
import http from '../api/http'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: localStorage.getItem('token') || null,
  }),
  getters: {
    isAuthenticated: (s) => !!s.token,
  },
  actions: {
    async login(email, password) {
      const { data } = await http.post('/login', { email, password })
      this.token = data.token
      localStorage.setItem('token', data.token)
      this.user = data.user
      return this.user
    },
    async fetchUser() {
      const { data } = await http.get('/me')
      this.user = data.data
      return this.user
    },
    async updateProfile(payload) {
      const { data } = await http.put('/profile', payload)
      this.user = data.data
      return this.user
    },
    async logout() {
      try { await http.post('/logout') } catch { /* ignore */ }
      this.token = null
      this.user = null
      localStorage.removeItem('token')
    },
  },
})

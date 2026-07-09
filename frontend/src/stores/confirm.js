import { defineStore } from 'pinia'

// Promise-based confirmation dialog: `await confirm.ask({ title, message })`.
export const useConfirmStore = defineStore('confirm', {
  state: () => ({ open: false, title: '', message: '', danger: false, _resolve: null }),
  actions: {
    ask({ title = 'Are you sure?', message = '', danger = true }) {
      this.title = title
      this.message = message
      this.danger = danger
      this.open = true
      return new Promise((resolve) => { this._resolve = resolve })
    },
    answer(ok) {
      this.open = false
      this._resolve?.(ok)
    },
  },
})

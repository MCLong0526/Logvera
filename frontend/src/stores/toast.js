import { defineStore } from 'pinia'

let nextId = 1

// Minimal toast queue; components read `items` and call push/remove.
export const useToastStore = defineStore('toast', {
  state: () => ({ items: [] }),
  actions: {
    push(message, type = 'success') {
      const id = nextId++
      this.items.push({ id, message, type })
      setTimeout(() => this.remove(id), 3500)
    },
    success(m) { this.push(m, 'success') },
    error(m) { this.push(m, 'error') },
    remove(id) { this.items = this.items.filter((t) => t.id !== id) },
  },
})

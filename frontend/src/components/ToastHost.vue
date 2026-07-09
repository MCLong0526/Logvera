<script setup>
import { useToastStore } from '../stores/toast'
import Icon from './Icon.vue'
const toast = useToastStore()
</script>

<template>
  <div class="fixed bottom-4 right-4 z-50 flex flex-col gap-2">
    <div
      v-for="t in toast.items"
      :key="t.id"
      class="flex items-center gap-3 rounded-lg px-4 py-3 text-sm text-white shadow-lg"
      :class="t.type === 'error' ? 'bg-red-600' : 'bg-slate-800'"
    >
      <span>{{ t.message }}</span>
      <button class="opacity-70 hover:opacity-100" @click="toast.remove(t.id)"><Icon name="close" class="h-4 w-4" /></button>
    </div>
  </div>
</template>

<script setup>
import Icon from './Icon.vue'
defineProps({ title: String })
const emit = defineEmits(['close'])
</script>

<template>
  <div class="fixed inset-0 z-40 flex items-center justify-center bg-black/40 p-4" @click.self="emit('close')">
    <div class="card w-full max-w-lg max-h-[90vh] overflow-y-auto p-5">
      <div class="mb-4 flex items-center justify-between">
        <h3 class="text-lg font-semibold text-slate-800">{{ title }}</h3>
        <button class="text-slate-400 hover:text-slate-600" @click="emit('close')"><Icon name="close" class="h-5 w-5" /></button>
      </div>
      <slot />
    </div>
  </div>
</template>

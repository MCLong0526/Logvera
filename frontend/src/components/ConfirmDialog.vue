<script setup>
import { useConfirmStore } from '../stores/confirm'
const confirm = useConfirmStore()
</script>

<template>
  <div v-if="confirm.open" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
    <div class="card w-full max-w-sm p-5">
      <h3 class="text-lg font-semibold text-slate-800">{{ confirm.title }}</h3>
      <p class="mt-2 text-sm text-slate-500">{{ confirm.message }}</p>
      <div class="mt-5 flex justify-end gap-2">
        <button class="btn-secondary" @click="confirm.answer(false)">Cancel</button>
        <button :class="confirm.danger ? 'btn-danger' : 'btn-primary'" @click="confirm.answer(true)">
          Confirm
        </button>
      </div>
    </div>
  </div>
</template>

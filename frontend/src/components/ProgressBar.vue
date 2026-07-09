<script setup>
defineProps({ value: { type: Number, default: 0 } })
</script>

<template>
  <div class="flex items-center gap-2">
    <div class="h-2 flex-1 rounded-full bg-slate-100">
      <div class="h-2 rounded-full bg-brand-500 transition-all" :style="{ width: `${value}%` }" />
    </div>
    <span class="w-9 text-right text-xs font-medium text-slate-500">{{ value }}%</span>
  </div>
</template>

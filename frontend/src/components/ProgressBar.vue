<script setup>
defineProps({ value: { type: Number, default: 0 } })
</script>

<template>
  <div class="flex items-center gap-2.5">
    <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-brand-100">
      <div class="h-full rounded-full bg-ink transition-all duration-500" :style="{ width: `${value}%` }" />
    </div>
    <span class="w-9 text-right font-mono text-xs tabular-nums text-stone-500">{{ value }}%</span>
  </div>
</template>

<script setup>
import { computed } from 'vue'
const props = defineProps({ name: { type: String, default: '?' }, size: { type: String, default: 'md' } })
const initials = computed(() => (props.name || '?').split(' ').map((w) => w[0]).slice(0, 2).join('').toUpperCase())
const sizes = { sm: 'h-6 w-6 text-[10px]', md: 'h-8 w-8 text-xs', lg: 'h-12 w-12 text-base' }
</script>

<template>
  <span
    class="inline-flex items-center justify-center rounded-full bg-brand-100 font-semibold text-brand-700"
    :class="sizes[size]"
    :title="name"
  >{{ initials }}</span>
</template>

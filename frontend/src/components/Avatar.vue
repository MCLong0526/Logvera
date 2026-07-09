<script setup>
import { computed } from 'vue'
const props = defineProps({
  name: { type: String, default: '?' },
  size: { type: String, default: 'md' },
  src: { type: String, default: '' },
})
const initials = computed(() => (props.name || '?').split(' ').map((w) => w[0]).slice(0, 2).join('').toUpperCase())
const sizes = { sm: 'h-6 w-6 text-[10px]', md: 'h-8 w-8 text-xs', lg: 'h-12 w-12 text-base', xl: 'h-20 w-20 text-xl' }
</script>

<template>
  <img
    v-if="src"
    :src="src"
    :alt="name"
    :title="name"
    class="inline-block shrink-0 rounded-full object-cover"
    :class="sizes[size]"
  />
  <span
    v-else
    class="inline-flex shrink-0 items-center justify-center rounded-full bg-brand-100 font-semibold text-brand-700"
    :class="sizes[size]"
    :title="name"
  >{{ initials }}</span>
</template>

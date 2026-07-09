<script setup>
import { computed } from 'vue'
import { STATUS, PRIORITY } from '../utils/labels'

const props = defineProps({ kind: { type: String, default: 'status' }, value: String })
const meta = computed(() => (props.kind === 'priority' ? PRIORITY : STATUS)[props.value] || { label: props.value, dot: 'bg-stone-400' })
</script>

<template>
  <span class="inline-flex items-center gap-1.5 rounded-full border border-line bg-surface px-2.5 py-0.5 text-xs font-medium text-stone-600">
    <span class="h-1.5 w-1.5 rounded-full" :class="meta.dot" />
    {{ meta.label }}
  </span>
</template>

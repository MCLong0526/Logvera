<script setup>
import { computed } from 'vue'
import { STATUS, PRIORITY } from '../utils/labels'

const props = defineProps({ kind: { type: String, default: 'status' }, value: String })
const meta = computed(() => (props.kind === 'priority' ? PRIORITY : STATUS)[props.value] || { label: props.value, class: 'bg-slate-100 text-slate-600' })
</script>

<template>
  <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium" :class="meta.class">
    {{ meta.label }}
  </span>
</template>

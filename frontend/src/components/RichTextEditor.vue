<script setup>
// ponytail: contenteditable + execCommand covers bold/italic/lists without an editor lib;
// swap for TipTap if headings/tables/images are ever needed.
import { ref, onMounted, watch } from 'vue'

const props = defineProps({
  modelValue: { type: String, default: '' },
  placeholder: { type: String, default: '' },
})
const emit = defineEmits(['update:modelValue'])
const el = ref(null)

const buttons = [
  { cmd: 'bold', label: 'B', class: 'font-bold', title: 'Bold' },
  { cmd: 'italic', label: 'I', class: 'italic', title: 'Italic' },
  { cmd: 'underline', label: 'U', class: 'underline', title: 'Underline' },
  { cmd: 'insertUnorderedList', label: '•―', title: 'Bullet list' },
  { cmd: 'insertOrderedList', label: '1.', title: 'Numbered list' },
]

function exec(cmd) {
  el.value.focus()
  document.execCommand(cmd)
  onInput()
}

function onInput() {
  // Report a visually empty editor as '' so required-checks work.
  const empty = !el.value.textContent.trim() && !el.value.querySelector('li')
  emit('update:modelValue', empty ? '' : el.value.innerHTML)
}

onMounted(() => { el.value.innerHTML = props.modelValue || '' })

// Reflect external changes (e.g. the form being reset) without clobbering typing.
watch(() => props.modelValue, (v) => {
  if (el.value && document.activeElement !== el.value && el.value.innerHTML !== (v || '')) {
    el.value.innerHTML = v || ''
  }
})
</script>

<template>
  <div class="w-full overflow-hidden rounded-md border border-line bg-surface transition focus-within:border-ink focus-within:ring-2 focus-within:ring-ink/10">
    <div class="flex gap-0.5 border-b border-line bg-brand-50 px-1.5 py-1">
      <button
        v-for="b in buttons"
        :key="b.cmd"
        type="button"
        :title="b.title"
        class="rounded px-2 py-0.5 font-mono text-xs text-stone-500 hover:bg-line hover:text-ink"
        :class="b.class"
        @mousedown.prevent="exec(b.cmd)"
      >{{ b.label }}</button>
    </div>
    <div
      ref="el"
      contenteditable="true"
      class="rich min-h-[5rem] px-3 py-2 text-sm text-ink outline-none"
      :data-placeholder="placeholder"
      @input="onInput"
    />
  </div>
</template>

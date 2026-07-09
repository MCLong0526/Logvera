<script setup>
import { ref, onMounted } from 'vue'
import http from '../api/http'
import { useAuthStore } from '../stores/auth'
import { useToastStore } from '../stores/toast'
import { useConfirmStore } from '../stores/confirm'
import Spinner from '../components/Spinner.vue'
import Modal from '../components/Modal.vue'
import Avatar from '../components/Avatar.vue'
import Icon from '../components/Icon.vue'
import { formatDate } from '../utils/labels'

const auth = useAuthStore()
const toast = useToastStore()
const confirm = useConfirmStore()

const loading = ref(true)
const boards = ref([])
const users = ref([])

const showModal = ref(false)
const form = ref({ name: '', member_ids: [] })

// Stage → colour, matching the status-dot language used app-wide.
const SEGMENTS = [
  { key: 'assigned', label: 'ASN', dot: 'bg-stone-400' },
  { key: 'in_progress', label: 'PRG', dot: 'bg-blue-500' },
  { key: 'closed', label: 'CLS', dot: 'bg-emerald-500' },
]

function pct(b, key) {
  if (!b.cards_count) return 0
  return ((b.stage_counts?.[key] ?? 0) / b.cards_count) * 100
}

async function load() {
  loading.value = true
  const { data } = await http.get('/boards')
  boards.value = data.data
  loading.value = false
}

// Everyone except me — I'm added as owner automatically.
async function loadUsers() {
  const { data } = await http.get('/users')
  users.value = data.data.filter((u) => u.id !== auth.user?.id)
}

function openCreate() {
  form.value = { name: '', member_ids: [] }
  showModal.value = true
}

async function save() {
  try {
    await http.post('/boards', form.value)
    toast.success('Board created')
    showModal.value = false
    load()
  } catch (e) {
    toast.error(e.response?.data?.message || 'Save failed')
  }
}

async function remove(b) {
  if (!(await confirm.ask({ title: 'Delete board?', message: `“${b.name}” and its cards will be removed.` }))) return
  await http.delete(`/boards/${b.id}`)
  toast.success('Board deleted')
  load()
}

onMounted(() => { load(); loadUsers() })
</script>

<template>
  <div>
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
      <div>
        <p class="font-mono text-[11px] uppercase tracking-widest text-stone-400">Workspace / Boards</p>
        <h1 class="mt-1 text-2xl font-bold tracking-tight text-ink">Task It</h1>
        <p class="mt-1 text-sm text-stone-500">Drag work through Assigned → In Progress → Close.</p>
      </div>
      <button class="btn-primary" @click="openCreate"><Icon name="plus" class="h-4 w-4" />New Board</button>
    </div>

    <Spinner v-if="loading" />
    <div v-else-if="!boards.length" class="rounded-lg border-2 border-dashed border-line p-14 text-center">
      <Icon name="board" class="mx-auto h-10 w-10 text-stone-300" />
      <p class="mt-3 font-medium text-stone-500">No boards yet</p>
      <p class="mt-1 text-sm text-stone-400">Create one and start dragging tasks through the stages.</p>
      <button class="btn-primary mt-5" @click="openCreate"><Icon name="plus" class="h-4 w-4" />New Board</button>
    </div>

    <div v-else class="stagger grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
      <div v-for="b in boards" :key="b.id" class="card group flex flex-col p-5 transition-shadow hover:shadow-lift">
        <div class="flex items-start justify-between">
          <span class="font-mono text-[11px] tracking-wider text-stone-400">BRD-{{ String(b.id).padStart(2, '0') }}</span>
          <button
            v-if="b.is_owner"
            class="hidden text-stone-300 transition-colors hover:text-red-500 group-hover:block"
            title="Delete board"
            @click="remove(b)"
          ><Icon name="trash" class="h-4 w-4" /></button>
        </div>

        <router-link :to="{ name: 'board', params: { id: b.id } }" class="mt-1 break-words text-lg font-semibold text-ink transition-colors hover:text-accent">
          {{ b.name }}
        </router-link>

        <!-- Stage distribution — the spec-sheet strip -->
        <div class="mt-4 flex h-1.5 overflow-hidden rounded-full bg-line">
          <div v-for="s in SEGMENTS" :key="s.key" class="transition-all duration-500" :class="s.dot" :style="{ width: pct(b, s.key) + '%' }" />
        </div>
        <div class="mt-2 flex items-center gap-3">
          <span v-for="s in SEGMENTS" :key="s.key" class="flex items-center gap-1 font-mono text-[10px] tracking-wider text-stone-400">
            <span class="h-1.5 w-1.5 rounded-full" :class="s.dot" />{{ s.label }} {{ b.stage_counts?.[s.key] ?? 0 }}
          </span>
        </div>

        <div class="mt-4 flex items-center justify-between border-t border-line pt-3 text-xs text-stone-400">
          <span class="flex items-center gap-1.5"><Avatar :name="b.owner?.name" :src="b.owner?.avatar" size="sm" />{{ b.owner?.name }}</span>
          <span class="font-mono tabular-nums">{{ b.members_count }} members · {{ formatDate(b.created_at) }}</span>
        </div>
      </div>
    </div>

    <Modal v-if="showModal" title="New Board" @close="showModal = false">
      <form class="space-y-4" @submit.prevent="save">
        <div><label class="label">Name</label><input v-model="form.name" class="input" required /></div>
        <div>
          <label class="label">Users</label>
          <div class="max-h-44 space-y-1 overflow-y-auto rounded-lg border border-line p-2">
            <label v-for="u in users" :key="u.id" class="flex cursor-pointer items-center gap-2 rounded px-2 py-1 text-sm hover:bg-brand-50">
              <input v-model="form.member_ids" type="checkbox" :value="u.id" class="h-4 w-4 rounded border-stone-300" />
              <Avatar :name="u.name" :src="u.avatar" size="sm" />
              <span class="text-stone-700">{{ u.name }}</span>
              <span class="text-xs text-stone-400">{{ u.email }}</span>
            </label>
            <p v-if="!users.length" class="px-2 py-1 text-xs text-stone-400">No other users to add.</p>
          </div>
          <p class="mt-1 text-xs text-stone-400">You are added as the owner automatically.</p>
        </div>
        <div class="flex justify-end gap-2">
          <button type="button" class="btn-secondary" @click="showModal = false">Cancel</button>
          <button type="submit" class="btn-primary">Create Board</button>
        </div>
      </form>
    </Modal>
  </div>
</template>

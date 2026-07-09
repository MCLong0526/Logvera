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
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-ink">Task It</h1>
        <p class="text-sm text-stone-500">Kanban boards — drag cards through Assigned, In Progress, and Close.</p>
      </div>
      <button class="btn-primary" @click="openCreate"><Icon name="plus" class="h-4 w-4" />New Board</button>
    </div>

    <Spinner v-if="loading" />
    <div v-else-if="!boards.length" class="card p-10 text-center text-stone-400">No boards yet — create one to get started.</div>
    <div v-else class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
      <div v-for="b in boards" :key="b.id" class="card flex flex-col p-4">
        <router-link :to="{ name: 'board', params: { id: b.id } }" class="flex items-center gap-2 font-semibold text-stone-800 hover:text-brand-600">
          <Icon name="board" class="h-4 w-4 text-stone-400" />{{ b.name }}
        </router-link>
        <div class="mt-3 flex items-center justify-between text-xs text-stone-400">
          <span class="flex items-center gap-1"><Avatar :name="b.owner?.name" :src="b.owner?.avatar" size="sm" /> {{ b.owner?.name }}</span>
          <span class="font-mono">{{ b.cards_count }} cards · {{ b.members_count }} members</span>
        </div>
        <div class="mt-2 font-mono text-[11px] text-stone-400">Created {{ formatDate(b.created_at) }}</div>
        <div v-if="b.is_owner" class="mt-3 flex gap-2 border-t border-stone-100 pt-3">
          <button class="btn-danger !py-1 text-xs" @click="remove(b)"><Icon name="trash" class="h-4 w-4" />Delete</button>
        </div>
      </div>
    </div>

    <Modal v-if="showModal" title="New Board" @close="showModal = false">
      <form class="space-y-4" @submit.prevent="save">
        <div><label class="label">Name</label><input v-model="form.name" class="input" required /></div>
        <div>
          <label class="label">Users</label>
          <div class="max-h-44 space-y-1 overflow-y-auto rounded-lg border border-stone-200 p-2">
            <label v-for="u in users" :key="u.id" class="flex cursor-pointer items-center gap-2 rounded px-2 py-1 text-sm hover:bg-stone-50">
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

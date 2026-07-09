<script setup>
import { ref, onMounted } from 'vue'
import http from '../api/http'
import { useToastStore } from '../stores/toast'
import { useConfirmStore } from '../stores/confirm'
import { useAuthStore } from '../stores/auth'
import Spinner from '../components/Spinner.vue'
import Modal from '../components/Modal.vue'
import Avatar from '../components/Avatar.vue'
import Icon from '../components/Icon.vue'
import { formatDate } from '../utils/labels'

const toast = useToastStore()
const confirm = useConfirmStore()
const auth = useAuthStore()

const loading = ref(true)
const projects = ref([])
const users = ref([])
const search = ref('')
const statusFilter = ref('')

const showModal = ref(false)
const editing = ref(null)
const form = ref({ name: '', description: '', status: 'active', due_date: '', member_ids: [] })

async function load() {
  loading.value = true
  const { data } = await http.get('/projects', { params: { search: search.value, status: statusFilter.value } })
  projects.value = data.data
  loading.value = false
}

// Everyone except me — I'm added as owner automatically.
async function loadUsers() {
  const { data } = await http.get('/users')
  users.value = data.data.filter((u) => u.id !== auth.user?.id)
}

function openCreate() {
  editing.value = null
  form.value = { name: '', description: '', status: 'active', due_date: '', member_ids: [] }
  showModal.value = true
}

async function openEdit(p) {
  editing.value = p
  form.value = { name: p.name, description: p.description, status: p.status, due_date: p.due_date || '', member_ids: [] }
  showModal.value = true
  // Pre-check current members (owner is implicit and always kept).
  const { data } = await http.get(`/projects/${p.id}/members`)
  form.value.member_ids = data.data.filter((m) => m.role !== 'owner').map((m) => m.id)
}

async function save() {
  try {
    if (editing.value) {
      await http.put(`/projects/${editing.value.id}`, form.value)
      toast.success('Project updated')
    } else {
      await http.post('/projects', form.value)
      toast.success('Project created')
    }
    showModal.value = false
    load()
  } catch (e) {
    toast.error(e.response?.data?.message || 'Save failed')
  }
}

async function archive(p) {
  await http.post(`/projects/${p.id}/archive`)
  toast.success(p.status === 'archived' ? 'Project restored' : 'Project archived')
  load()
}

async function remove(p) {
  if (!(await confirm.ask({ title: 'Delete project?', message: `“${p.name}” and its tasks will be removed.` }))) return
  await http.delete(`/projects/${p.id}`)
  toast.success('Project deleted')
  load()
}

onMounted(() => { load(); loadUsers() })
</script>

<template>
  <div>
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
      <h1 class="text-2xl font-bold tracking-tight text-ink">Projects</h1>
      <button class="btn-primary" @click="openCreate"><Icon name="plus" class="h-4 w-4" />New Project</button>
    </div>

    <div class="mb-4 flex flex-wrap gap-3">
      <input v-model="search" class="input max-w-xs" placeholder="Search projects…" @keyup.enter="load" />
      <select v-model="statusFilter" class="input max-w-[10rem]" @change="load">
        <option value="">All statuses</option>
        <option value="active">Active</option>
        <option value="archived">Archived</option>
      </select>
      <button class="btn-secondary" @click="load">Filter</button>
    </div>

    <Spinner v-if="loading" />
    <div v-else-if="!projects.length" class="card p-10 text-center text-stone-400">No projects found.</div>
    <div v-else class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
      <div v-for="p in projects" :key="p.id" class="card flex flex-col p-4">
        <div class="flex items-start justify-between">
          <router-link :to="{ name: 'project', params: { id: p.id } }" class="font-semibold text-stone-800 hover:text-brand-600">
            {{ p.name }}
          </router-link>
          <span v-if="p.status === 'archived'" class="rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-700">Archived</span>
        </div>
        <p class="mt-1 line-clamp-2 flex-1 text-sm text-stone-500">{{ p.description || 'No description' }}</p>
        <div v-if="p.due_date" class="mt-2 inline-flex w-fit items-center gap-1.5 rounded-md bg-brand-50 px-2 py-0.5 font-mono text-[11px] text-stone-600">
          <Icon name="calendar" class="h-3.5 w-3.5" />Due {{ formatDate(p.due_date) }}
        </div>
        <div class="mt-3 flex items-center justify-between text-xs text-stone-400">
          <span class="flex items-center gap-1"><Avatar :name="p.owner?.name" size="sm" /> {{ p.owner?.name }}</span>
          <span>{{ p.tasks_count }} tasks · {{ formatDate(p.created_at) }}</span>
        </div>
        <div v-if="p.is_owner" class="mt-3 flex gap-2 border-t border-stone-100 pt-3">
          <button class="btn-secondary !py-1 text-xs" @click="openEdit(p)"><Icon name="edit" class="h-4 w-4" />Edit</button>
          <button class="btn-secondary !py-1 text-xs" @click="archive(p)"><Icon name="archive" class="h-4 w-4" />{{ p.status === 'archived' ? 'Restore' : 'Archive' }}</button>
          <button class="btn-danger !py-1 text-xs" @click="remove(p)"><Icon name="trash" class="h-4 w-4" />Delete</button>
        </div>
      </div>
    </div>

    <Modal v-if="showModal" :title="editing ? 'Edit Project' : 'New Project'" @close="showModal = false">
      <form class="space-y-4" @submit.prevent="save">
        <div><label class="label">Name</label><input v-model="form.name" class="input" required /></div>
        <div><label class="label">Description</label><textarea v-model="form.description" class="input" rows="3" /></div>
        <div><label class="label">Due Date</label><input v-model="form.due_date" type="date" class="input" /></div>
        <div v-if="editing">
          <label class="label">Status</label>
          <select v-model="form.status" class="input">
            <option value="active">Active</option>
            <option value="archived">Archived</option>
          </select>
        </div>
        <div>
          <label class="label">{{ editing ? 'Members' : 'Add Members' }}</label>
          <div class="max-h-44 space-y-1 overflow-y-auto rounded-lg border border-stone-200 p-2">
            <label v-for="u in users" :key="u.id" class="flex cursor-pointer items-center gap-2 rounded px-2 py-1 text-sm hover:bg-stone-50">
              <input v-model="form.member_ids" type="checkbox" :value="u.id" class="h-4 w-4 rounded border-stone-300" />
              <Avatar :name="u.name" size="sm" />
              <span class="text-stone-700">{{ u.name }}</span>
              <span class="text-xs text-stone-400">{{ u.email }}</span>
            </label>
            <p v-if="!users.length" class="px-2 py-1 text-xs text-stone-400">No other users to add.</p>
          </div>
          <p class="mt-1 text-xs text-stone-400">{{ editing ? 'The owner is always kept; unchecking removes a member.' : 'You are added as the owner automatically.' }}</p>
        </div>
        <div class="flex justify-end gap-2">
          <button type="button" class="btn-secondary" @click="showModal = false">Cancel</button>
          <button type="submit" class="btn-primary">Save</button>
        </div>
      </form>
    </Modal>
  </div>
</template>

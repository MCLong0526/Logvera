<script setup>
import { ref, onMounted } from 'vue'
import http from '../api/http'
import { useToastStore } from '../stores/toast'
import { useConfirmStore } from '../stores/confirm'
import Spinner from '../components/Spinner.vue'
import Avatar from '../components/Avatar.vue'
import { formatDate, formatBytes } from '../utils/labels'

const toast = useToastStore()
const confirm = useConfirmStore()

const loading = ref(true)
const files = ref([])
const projects = ref([])
const users = ref([])
const filters = ref({ project_id: '', user_id: '', date: '' })

async function load() {
  loading.value = true
  const { data } = await http.get('/files', { params: filters.value })
  files.value = data.data
  loading.value = false
}

async function remove(f) {
  if (!(await confirm.ask({ title: 'Delete file?', message: f.original_name }))) return
  await http.delete(`/files/${f.id}`)
  toast.success('File deleted')
  load()
}

onMounted(async () => {
  const [p, u] = await Promise.all([http.get('/projects', { params: { per_page: 100 } }), http.get('/users')])
  projects.value = p.data.data
  users.value = u.data.data
  load()
})
</script>

<template>
  <div>
    <h1 class="mb-5 text-2xl font-semibold text-slate-800">Files</h1>

    <div class="mb-4 flex flex-wrap gap-3">
      <select v-model="filters.project_id" class="input max-w-[12rem]" @change="load">
        <option value="">All projects</option>
        <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
      </select>
      <select v-model="filters.user_id" class="input max-w-[12rem]" @change="load">
        <option value="">All users</option>
        <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
      </select>
      <input v-model="filters.date" type="date" class="input max-w-[12rem]" @change="load" />
      <button class="btn-secondary" @click="filters = { project_id: '', user_id: '', date: '' }; load()">Reset</button>
    </div>

    <Spinner v-if="loading" />
    <div v-else-if="!files.length" class="card p-10 text-center text-slate-400">No files found.</div>
    <div v-else class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
      <div v-for="f in files" :key="f.id" class="card group relative overflow-hidden">
        <a :href="f.url" target="_blank">
          <img v-if="f.is_image" :src="f.url" class="h-32 w-full object-cover" />
          <div v-else class="flex h-32 items-center justify-center bg-slate-50 text-4xl">📄</div>
        </a>
        <button class="absolute right-2 top-2 hidden rounded bg-white/90 px-1.5 text-red-500 group-hover:block" @click="remove(f)">✕</button>
        <div class="p-3">
          <p class="truncate text-sm font-medium text-slate-700">{{ f.original_name }}</p>
          <p class="mt-0.5 flex items-center gap-1 text-xs text-slate-400">
            <Avatar :name="f.user?.name" size="sm" />{{ formatDate(f.created_at) }} · {{ formatBytes(f.size) }}
          </p>
          <a :href="f.url" :download="f.original_name" target="_blank" class="mt-2 inline-block text-xs font-medium text-brand-600 hover:underline">Download</a>
        </div>
      </div>
    </div>
  </div>
</template>

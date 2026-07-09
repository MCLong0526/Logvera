<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import http from '../api/http'
import { useToastStore } from '../stores/toast'
import Spinner from '../components/Spinner.vue'
import Badge from '../components/Badge.vue'
import Avatar from '../components/Avatar.vue'
import ProgressBar from '../components/ProgressBar.vue'
import { formatDate, formatDateTime, formatBytes, TYPE, STATUS_OPTIONS } from '../utils/labels'

const route = useRoute()
const toast = useToastStore()

const loading = ref(true)
const task = ref(null)
const tab = ref('overview')
const tabs = [
  { key: 'overview', label: 'Overview' },
  { key: 'updates', label: 'Update Logs' },
  { key: 'files', label: 'Files' },
]

const update = ref({ description: '', progress_after: null, status_after: '' })
const updateFiles = ref([])
const submitting = ref(false)

async function load() {
  loading.value = true
  const { data } = await http.get(`/tasks/${route.params.id}`)
  task.value = data.data
  update.value.progress_after = task.value.progress
  update.value.status_after = task.value.status
  loading.value = false
}

async function submitUpdate() {
  submitting.value = true
  const fd = new FormData()
  fd.append('description', update.value.description)
  if (update.value.progress_after !== null) fd.append('progress_after', update.value.progress_after)
  if (update.value.status_after) fd.append('status_after', update.value.status_after)
  updateFiles.value.forEach((f) => fd.append('attachments[]', f))
  try {
    await http.post(`/tasks/${route.params.id}/updates`, fd)
    toast.success('Update posted')
    update.value.description = ''
    updateFiles.value = []
    await load()
    tab.value = 'updates'
  } catch (e) {
    toast.error(e.response?.data?.message || 'Could not post update')
  } finally {
    submitting.value = false
  }
}

function onFiles(e) {
  // Append so files picked across multiple selections all upload.
  updateFiles.value.push(...Array.from(e.target.files))
  e.target.value = ''
}
function removeFile(i) { updateFiles.value.splice(i, 1) }

onMounted(load)
</script>

<template>
  <Spinner v-if="loading" />
  <div v-else-if="task">
    <router-link :to="{ name: 'project', params: { id: task.project_id } }" class="text-sm text-slate-400 hover:text-brand-600">
      ← {{ task.project?.name }}
    </router-link>

    <div class="mt-2 flex flex-wrap items-start justify-between gap-3">
      <div>
        <h1 class="text-2xl font-semibold text-slate-800">{{ task.title }}</h1>
        <div class="mt-2 flex flex-wrap items-center gap-2">
          <Badge :value="task.status" />
          <Badge kind="priority" :value="task.priority" />
          <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs text-slate-500">{{ TYPE[task.type] }}</span>
        </div>
      </div>
      <div class="w-56"><ProgressBar :value="task.progress" /></div>
    </div>

    <!-- Tabs -->
    <div class="mt-5 border-b border-slate-200">
      <nav class="flex gap-6">
        <button
          v-for="t in tabs"
          :key="t.key"
          class="border-b-2 pb-2 text-sm font-medium"
          :class="tab === t.key ? 'border-brand-600 text-brand-600' : 'border-transparent text-slate-500 hover:text-slate-700'"
          @click="tab = t.key"
        >
          {{ t.label }}
          <span v-if="t.key === 'updates'" class="ml-1 text-xs text-slate-400">{{ task.updates.length }}</span>
        </button>
      </nav>
    </div>

    <!-- Overview -->
    <div v-if="tab === 'overview'" class="mt-5 grid grid-cols-1 gap-5 lg:grid-cols-3">
      <div class="card p-5 lg:col-span-2">
        <h3 class="mb-2 text-sm font-semibold text-slate-700">Description</h3>
        <p class="whitespace-pre-line text-sm text-slate-600">{{ task.description || 'No description provided.' }}</p>
      </div>
      <div class="card divide-y divide-slate-100 p-0 text-sm">
        <div class="flex items-center justify-between p-4">
          <span class="text-slate-400">Assignee</span>
          <span class="flex items-center gap-2 font-medium text-slate-700"><Avatar :name="task.assignee?.name" size="sm" v-if="task.assignee" />{{ task.assignee?.name || 'Unassigned' }}</span>
        </div>
        <div class="flex items-center justify-between p-4"><span class="text-slate-400">Created by</span><span class="font-medium text-slate-700">{{ task.creator?.name }}</span></div>
        <div class="flex items-center justify-between p-4"><span class="text-slate-400">Target date</span><span class="font-medium text-slate-700">{{ formatDate(task.target_date) }}</span></div>
        <div class="flex items-center justify-between p-4"><span class="text-slate-400">Target time</span><span class="font-medium text-slate-700">{{ task.target_time || '—' }}</span></div>
        <div class="flex items-center justify-between p-4"><span class="text-slate-400">Created</span><span class="font-medium text-slate-700">{{ formatDate(task.created_at) }}</span></div>
      </div>
    </div>

    <!-- Update Logs -->
    <div v-if="tab === 'updates'" class="mt-5 grid grid-cols-1 gap-5 lg:grid-cols-3">
      <div class="lg:col-span-2">
        <div v-if="!task.updates.length" class="card p-8 text-center text-slate-400">No updates yet.</div>
        <ol class="relative ml-3 border-l-2 border-slate-100">
          <li v-for="u in task.updates" :key="u.id" class="mb-5 ml-5">
            <span class="absolute -left-[9px] mt-1 h-4 w-4 rounded-full border-2 border-white bg-brand-500" />
            <div class="card p-4">
              <div class="mb-1 flex items-center gap-2">
                <Avatar :name="u.user?.name" size="sm" />
                <span class="text-sm font-medium text-slate-700">{{ u.user?.name }}</span>
                <span class="text-xs text-slate-400">{{ formatDateTime(u.created_at) }}</span>
              </div>
              <p class="whitespace-pre-line text-sm text-slate-600">{{ u.description }}</p>
              <div class="mt-2 flex flex-wrap items-center gap-2">
                <Badge v-if="u.status_after" :value="u.status_after" />
                <span v-if="u.progress_after !== null" class="text-xs text-slate-400">Progress → {{ u.progress_after }}%</span>
              </div>
              <div v-if="u.attachments?.length" class="mt-2 flex flex-wrap gap-2">
                <a v-for="a in u.attachments" :key="a.id" :href="a.url" target="_blank" class="rounded bg-slate-50 px-2 py-1 text-xs text-brand-600 hover:underline">📎 {{ a.original_name }}</a>
              </div>
            </div>
          </li>
        </ol>
      </div>
      <div class="card h-fit p-4">
        <h3 class="mb-3 text-sm font-semibold text-slate-700">Post an Update</h3>
        <form class="space-y-3" @submit.prevent="submitUpdate">
          <textarea v-model="update.description" class="input" rows="3" placeholder="What changed?" required />
          <div class="grid grid-cols-2 gap-3">
            <div><label class="label">Progress %</label><input v-model.number="update.progress_after" type="number" min="0" max="100" class="input" /></div>
            <div><label class="label">Status</label><select v-model="update.status_after" class="input"><option v-for="o in STATUS_OPTIONS" :key="o.value" :value="o.value">{{ o.label }}</option></select></div>
          </div>
          <input type="file" multiple class="text-sm" @change="onFiles" />
          <ul v-if="updateFiles.length" class="space-y-1">
            <li v-for="(f, i) in updateFiles" :key="i" class="flex items-center justify-between rounded bg-slate-50 px-2 py-1 text-xs">
              <span class="truncate">📎 {{ f.name }}</span>
              <button type="button" class="text-slate-400 hover:text-red-500" @click="removeFile(i)">✕</button>
            </li>
          </ul>
          <button type="submit" class="btn-primary w-full" :disabled="submitting">{{ submitting ? 'Posting…' : 'Post Update' }}</button>
        </form>
      </div>
    </div>

    <!-- Files -->
    <div v-if="tab === 'files'" class="mt-5">
      <div v-if="!task.attachments.length" class="card p-8 text-center text-slate-400">No files attached.</div>
      <div v-else class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
        <a v-for="a in task.attachments" :key="a.id" :href="a.url" target="_blank" class="card overflow-hidden hover:border-brand-200">
          <img v-if="a.is_image" :src="a.url" class="h-28 w-full object-cover" />
          <div v-else class="flex h-28 items-center justify-center bg-slate-50 text-3xl">📄</div>
          <div class="p-2">
            <p class="truncate text-xs font-medium text-slate-700">{{ a.original_name }}</p>
            <p class="text-[10px] text-slate-400">{{ formatBytes(a.size) }}</p>
          </div>
        </a>
      </div>
    </div>
  </div>
</template>

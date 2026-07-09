<script setup>
import { ref, onMounted, nextTick } from 'vue'
import { useRoute } from 'vue-router'
import http from '../api/http'
import { useToastStore } from '../stores/toast'
import Spinner from '../components/Spinner.vue'
import Badge from '../components/Badge.vue'
import Avatar from '../components/Avatar.vue'
import ProgressBar from '../components/ProgressBar.vue'
import Icon from '../components/Icon.vue'
import RichTextEditor from '../components/RichTextEditor.vue'
import { formatDate, formatDateTime, formatBytes, TYPE, STATUS_OPTIONS } from '../utils/labels'
import { richHtml } from '../utils/richtext'

const route = useRoute()
const toast = useToastStore()

const loading = ref(true)
const task = ref(null)
// Deep-link support: /tasks/:id?tab=updates&log=42 opens the log tab and highlights the log.
const tab = ref(['overview', 'updates', 'files'].includes(route.query.tab) ? route.query.tab : 'overview')
const highlightLog = ref(Number(route.query.log) || null)
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

  // Scroll the deep-linked log into view once rendered.
  if (highlightLog.value) {
    await nextTick()
    document.getElementById(`log-${highlightLog.value}`)?.scrollIntoView({ behavior: 'smooth', block: 'center' })
  }
}

async function submitUpdate() {
  if (!update.value.description) {
    toast.error('Please write what changed')
    return
  }
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
    <router-link :to="{ name: 'project', params: { id: task.project_id } }" class="inline-flex items-center gap-1 text-sm text-stone-400 hover:text-brand-600">
      <Icon name="back" class="h-4 w-4" />{{ task.project?.name }}
    </router-link>

    <div class="mt-2 flex flex-wrap items-start justify-between gap-3">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-ink">{{ task.title }}</h1>
        <div class="mt-2 flex flex-wrap items-center gap-2">
          <Badge :value="task.status" />
          <Badge kind="priority" :value="task.priority" />
          <span class="rounded-full bg-stone-100 px-2.5 py-0.5 text-xs text-stone-500">{{ TYPE[task.type] }}</span>
        </div>
      </div>
      <div class="w-56"><ProgressBar :value="task.progress" /></div>
    </div>

    <!-- Tabs -->
    <div class="mt-5 border-b border-stone-200">
      <nav class="flex gap-6">
        <button
          v-for="t in tabs"
          :key="t.key"
          class="border-b-2 pb-2 text-sm font-medium"
          :class="tab === t.key ? 'border-brand-600 text-brand-600' : 'border-transparent text-stone-500 hover:text-stone-700'"
          @click="tab = t.key"
        >
          {{ t.label }}
          <span v-if="t.key === 'updates'" class="ml-1 text-xs text-stone-400">{{ task.updates.length }}</span>
        </button>
      </nav>
    </div>

    <!-- Overview -->
    <div v-if="tab === 'overview'" class="mt-5 grid grid-cols-1 gap-5 lg:grid-cols-3">
      <div class="card p-5 lg:col-span-2">
        <h3 class="mb-2 text-sm font-semibold text-stone-700">Description</h3>
        <div class="rich text-sm text-stone-600" v-html="richHtml(task.description) || 'No description provided.'" />
      </div>
      <div class="card divide-y divide-stone-100 p-0 text-sm">
        <div class="flex items-start justify-between gap-3 p-4">
          <span class="text-stone-400">Assignees</span>
          <span v-if="!task.assignees?.length" class="font-medium text-stone-700">Unassigned</span>
          <span v-else class="flex flex-col items-end gap-1">
            <span v-for="a in task.assignees" :key="a.id" class="flex items-center gap-2 font-medium text-stone-700">
              <Avatar :name="a.name" :src="a.avatar" size="sm" />{{ a.name }}
            </span>
          </span>
        </div>
        <div class="flex items-center justify-between p-4"><span class="text-stone-400">Created by</span><span class="font-medium text-stone-700">{{ task.creator?.name }}</span></div>
        <div class="flex items-center justify-between p-4"><span class="text-stone-400">Target date</span><span class="font-medium text-stone-700">{{ formatDate(task.target_date) }}</span></div>
        <div class="flex items-center justify-between p-4"><span class="text-stone-400">Target time</span><span class="font-medium text-stone-700">{{ task.target_time || '—' }}</span></div>
        <div class="flex items-center justify-between p-4"><span class="text-stone-400">Created</span><span class="font-medium text-stone-700">{{ formatDate(task.created_at) }}</span></div>
      </div>
    </div>

    <!-- Update Logs -->
    <div v-if="tab === 'updates'" class="mt-5 grid grid-cols-1 gap-5 lg:grid-cols-3">
      <div class="lg:col-span-2">
        <div v-if="!task.updates.length" class="card p-8 text-center text-stone-400">No updates yet.</div>
        <ol class="relative ml-3 border-l-2 border-stone-100">
          <li v-for="u in task.updates" :key="u.id" :id="`log-${u.id}`" class="mb-5 ml-5 scroll-mt-24">
            <span class="absolute -left-[9px] mt-1 h-4 w-4 rounded-full border-2 border-white bg-brand-500" />
            <div class="card p-4 transition-shadow" :class="{ 'ring-2 ring-accent': highlightLog === u.id }">
              <div class="mb-1 flex items-center gap-2">
                <Avatar :name="u.user?.name" :src="u.user?.avatar" size="sm" />
                <span class="text-sm font-medium text-stone-700">{{ u.user?.name }}</span>
                <span class="text-xs text-stone-400">{{ formatDateTime(u.created_at) }}</span>
              </div>
              <div class="rich text-sm text-stone-600" v-html="richHtml(u.description)" />
              <div class="mt-2 flex flex-wrap items-center gap-2">
                <Badge v-if="u.status_after" :value="u.status_after" />
                <span v-if="u.progress_after !== null" class="text-xs text-stone-400">Progress → {{ u.progress_after }}%</span>
              </div>
              <div v-if="u.attachments?.length" class="mt-2 flex flex-wrap gap-2">
                <a v-for="a in u.attachments" :key="a.id" :href="a.url" target="_blank" class="inline-flex items-center gap-1 rounded bg-stone-50 px-2 py-1 text-xs text-brand-600 hover:underline"><Icon name="paperclip" class="h-3.5 w-3.5" />{{ a.original_name }}</a>
              </div>
            </div>
          </li>
        </ol>
      </div>
      <div class="card h-fit p-4">
        <h3 class="mb-3 text-sm font-semibold text-stone-700">Post an Update</h3>
        <form class="space-y-3" @submit.prevent="submitUpdate">
          <RichTextEditor v-model="update.description" placeholder="What changed?" />
          <div class="grid grid-cols-2 gap-3">
            <div><label class="label">Progress %</label><input v-model.number="update.progress_after" type="number" min="0" max="100" class="input" /></div>
            <div><label class="label">Status</label><select v-model="update.status_after" class="input"><option v-for="o in STATUS_OPTIONS" :key="o.value" :value="o.value">{{ o.label }}</option></select></div>
          </div>
          <input type="file" multiple class="text-sm" @change="onFiles" />
          <ul v-if="updateFiles.length" class="space-y-1">
            <li v-for="(f, i) in updateFiles" :key="i" class="flex items-center justify-between rounded bg-stone-50 px-2 py-1 text-xs">
              <span class="flex items-center gap-1 truncate"><Icon name="paperclip" class="h-3.5 w-3.5 shrink-0" />{{ f.name }}</span>
              <button type="button" class="text-stone-400 hover:text-red-500" @click="removeFile(i)"><Icon name="close" class="h-3.5 w-3.5" /></button>
            </li>
          </ul>
          <button type="submit" class="btn-primary w-full" :disabled="submitting">{{ submitting ? 'Posting…' : 'Post Update' }}</button>
        </form>
      </div>
    </div>

    <!-- Files -->
    <div v-if="tab === 'files'" class="mt-5">
      <div v-if="!task.attachments.length" class="card p-8 text-center text-stone-400">No files attached.</div>
      <div v-else class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
        <a v-for="a in task.attachments" :key="a.id" :href="a.url" target="_blank" class="card overflow-hidden hover:border-brand-200">
          <img v-if="a.is_image" :src="a.url" class="h-28 w-full object-cover" />
          <div v-else class="flex h-28 items-center justify-center bg-stone-50 text-stone-300"><Icon name="doc" class="h-10 w-10" /></div>
          <div class="p-2">
            <p class="truncate text-xs font-medium text-stone-700">{{ a.original_name }}</p>
            <p class="text-[10px] text-stone-400">{{ formatBytes(a.size) }}</p>
          </div>
        </a>
      </div>
    </div>
  </div>
</template>

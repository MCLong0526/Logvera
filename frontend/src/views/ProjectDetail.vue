<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import http from '../api/http'
import { useToastStore } from '../stores/toast'
import { useConfirmStore } from '../stores/confirm'
import Spinner from '../components/Spinner.vue'
import Modal from '../components/Modal.vue'
import Badge from '../components/Badge.vue'
import Avatar from '../components/Avatar.vue'
import ProgressBar from '../components/ProgressBar.vue'
import Icon from '../components/Icon.vue'
import { formatDate, TYPE, TYPE_OPTIONS, STATUS_OPTIONS, PRIORITY_OPTIONS } from '../utils/labels'

const route = useRoute()
const toast = useToastStore()
const confirm = useConfirmStore()

const loading = ref(true)
const project = ref(null)
const members = ref([])
const tasks = ref([])
const allUsers = ref([])

const showTaskModal = ref(false)
const showMemberModal = ref(false)
const newMemberId = ref('')
const files = ref([])
const form = ref(blankTask())

function blankTask() {
  return { title: '', description: '', type: 'development', priority: 'medium', status: 'not_started', progress: 0, target_date: '', target_time: '', assigned_user_id: '' }
}

// Group tasks by target_date, newest first.
const grouped = computed(() => {
  const map = {}
  for (const t of tasks.value) {
    const key = t.target_date || 'No date'
    ;(map[key] ||= []).push(t)
  }
  return Object.keys(map)
    .sort((a, b) => (a === 'No date' ? 1 : b === 'No date' ? -1 : b.localeCompare(a)))
    .map((date) => ({ date, tasks: map[date] }))
})

const availableUsers = computed(() =>
  allUsers.value.filter((u) => !members.value.some((m) => m.id === u.id)),
)

async function load() {
  loading.value = true
  const [p, m, t] = await Promise.all([
    http.get(`/projects/${route.params.id}`),
    http.get(`/projects/${route.params.id}/members`),
    http.get('/tasks', { params: { project_id: route.params.id, per_page: 100 } }),
  ])
  project.value = p.data.data
  members.value = m.data.data
  tasks.value = t.data.data
  loading.value = false
}

async function loadUsers() {
  const { data } = await http.get('/users')
  allUsers.value = data.data
}

async function createTask() {
  const fd = new FormData()
  Object.entries(form.value).forEach(([k, v]) => { if (v !== '' && v !== null) fd.append(k, v) })
  files.value.forEach((f) => fd.append('attachments[]', f))
  try {
    await http.post(`/projects/${route.params.id}/tasks`, fd)
    toast.success('Task created')
    showTaskModal.value = false
    form.value = blankTask()
    files.value = []
    load()
  } catch (e) {
    toast.error(e.response?.data?.message || 'Could not create task')
  }
}

async function addMember() {
  if (!newMemberId.value) return
  try {
    await http.post(`/projects/${route.params.id}/members`, { user_id: newMemberId.value })
    toast.success('Member added')
    newMemberId.value = ''
    showMemberModal.value = false
    const { data } = await http.get(`/projects/${route.params.id}/members`)
    members.value = data.data
  } catch (e) {
    toast.error(e.response?.data?.message || 'Could not add member')
  }
}

async function removeMember(m) {
  if (!(await confirm.ask({ title: 'Remove member?', message: `Remove ${m.name} from this project?` }))) return
  await http.delete(`/projects/${route.params.id}/members/${m.id}`)
  members.value = members.value.filter((x) => x.id !== m.id)
  toast.success('Member removed')
}

function onFiles(e) {
  // Append so files picked across multiple selections all upload.
  files.value.push(...Array.from(e.target.files))
  e.target.value = ''
}
function removeFile(i) { files.value.splice(i, 1) }

onMounted(async () => { await load(); loadUsers() })
</script>

<template>
  <Spinner v-if="loading" />
  <div v-else-if="project">
    <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
      <div>
        <router-link :to="{ name: 'projects' }" class="inline-flex items-center gap-1 text-sm text-stone-400 hover:text-brand-600"><Icon name="back" class="h-4 w-4" />Projects</router-link>
        <h1 class="mt-1 text-2xl font-bold tracking-tight text-ink">{{ project.name }}</h1>
        <p class="text-sm text-stone-500">{{ project.description }}</p>
        <div v-if="project.due_date" class="mt-2 inline-flex items-center gap-1.5 rounded-md bg-brand-50 px-2.5 py-1 font-mono text-xs text-stone-600">
          <Icon name="calendar" class="h-4 w-4" />Due {{ formatDate(project.due_date) }}
        </div>
      </div>
      <button class="btn-primary" @click="showTaskModal = true"><Icon name="plus" class="h-4 w-4" />New Task</button>
    </div>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
      <!-- Timeline -->
      <div class="lg:col-span-2">
        <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-stone-400">Task Timeline</h2>
        <div v-if="!tasks.length" class="card p-8 text-center text-stone-400">No tasks yet.</div>
        <div v-for="group in grouped" :key="group.date" class="mb-6">
          <div class="mb-2 flex items-center gap-2">
            <span class="h-2 w-2 rounded-full bg-brand-500" />
            <span class="text-sm font-semibold text-stone-600">
              {{ group.date === 'No date' ? 'No target date' : formatDate(group.date) }}
            </span>
          </div>
          <div class="ml-1 space-y-2 border-l-2 border-stone-100 pl-4">
            <router-link
              v-for="t in group.tasks"
              :key="t.id"
              :to="{ name: 'task', params: { id: t.id } }"
              class="card flex items-center gap-3 p-3 hover:border-brand-200"
            >
              <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-medium text-stone-800">{{ t.title }}</p>
                <p class="text-xs text-stone-400">{{ TYPE[t.type] }} · {{ t.assignee?.name || 'Unassigned' }}</p>
              </div>
              <Badge kind="priority" :value="t.priority" />
              <Badge :value="t.status" />
              <div class="hidden w-28 sm:block"><ProgressBar :value="t.progress" /></div>
            </router-link>
          </div>
        </div>
      </div>

      <!-- Members -->
      <div>
        <div class="mb-2 flex items-center justify-between">
          <h2 class="text-sm font-semibold uppercase tracking-wide text-stone-400">Members</h2>
          <button v-if="project.is_owner" class="inline-flex items-center gap-1 text-xs text-brand-600 hover:underline" @click="showMemberModal = true"><Icon name="plus" class="h-3.5 w-3.5" />Add</button>
        </div>
        <div class="card divide-y divide-stone-100">
          <div v-for="m in members" :key="m.id" class="flex items-center gap-3 p-3">
            <Avatar :name="m.name" size="sm" />
            <div class="min-w-0 flex-1">
              <p class="truncate text-sm font-medium text-stone-700">{{ m.name }}</p>
              <p class="text-xs text-stone-400">{{ m.job_title || m.email }}</p>
            </div>
            <span class="rounded-full bg-stone-100 px-2 py-0.5 text-xs capitalize text-stone-500">{{ m.role }}</span>
            <button v-if="project.is_owner && m.role !== 'owner'" class="text-stone-300 hover:text-red-500" @click="removeMember(m)"><Icon name="close" class="h-4 w-4" /></button>
          </div>
        </div>
      </div>
    </div>

    <!-- New task modal -->
    <Modal v-if="showTaskModal" title="New Task" @close="showTaskModal = false">
      <form class="space-y-3" @submit.prevent="createTask">
        <div><label class="label">Title</label><input v-model="form.title" class="input" required /></div>
        <div><label class="label">Description</label><textarea v-model="form.description" class="input" rows="2" /></div>
        <div class="grid grid-cols-2 gap-3">
          <div><label class="label">Type</label><select v-model="form.type" class="input"><option v-for="o in TYPE_OPTIONS" :key="o.value" :value="o.value">{{ o.label }}</option></select></div>
          <div><label class="label">Priority</label><select v-model="form.priority" class="input"><option v-for="o in PRIORITY_OPTIONS" :key="o.value" :value="o.value">{{ o.label }}</option></select></div>
          <div><label class="label">Status</label><select v-model="form.status" class="input"><option v-for="o in STATUS_OPTIONS" :key="o.value" :value="o.value">{{ o.label }}</option></select></div>
          <div><label class="label">Progress %</label><input v-model.number="form.progress" type="number" min="0" max="100" class="input" /></div>
          <div><label class="label">Target Date</label><input v-model="form.target_date" type="date" class="input" /></div>
          <div><label class="label">Target Time</label><input v-model="form.target_time" type="time" class="input" /></div>
        </div>
        <div>
          <label class="label">Assignee</label>
          <select v-model="form.assigned_user_id" class="input">
            <option value="">Unassigned</option>
            <option v-for="m in members" :key="m.id" :value="m.id">{{ m.name }}</option>
          </select>
        </div>
        <div>
          <label class="label">Attachments</label>
          <input type="file" multiple class="text-sm" @change="onFiles" />
          <ul v-if="files.length" class="mt-2 space-y-1">
            <li v-for="(f, i) in files" :key="i" class="flex items-center justify-between rounded bg-stone-50 px-2 py-1 text-xs">
              <span class="flex items-center gap-1 truncate"><Icon name="paperclip" class="h-3.5 w-3.5 shrink-0" />{{ f.name }}</span>
              <button type="button" class="text-stone-400 hover:text-red-500" @click="removeFile(i)"><Icon name="close" class="h-3.5 w-3.5" /></button>
            </li>
          </ul>
        </div>
        <div class="flex justify-end gap-2 pt-1">
          <button type="button" class="btn-secondary" @click="showTaskModal = false">Cancel</button>
          <button type="submit" class="btn-primary">Create Task</button>
        </div>
      </form>
    </Modal>

    <!-- Add member modal -->
    <Modal v-if="showMemberModal" title="Add Member" @close="showMemberModal = false">
      <form class="space-y-4" @submit.prevent="addMember">
        <div>
          <label class="label">User</label>
          <select v-model="newMemberId" class="input" required>
            <option value="">Select a user…</option>
            <option v-for="u in availableUsers" :key="u.id" :value="u.id">{{ u.name }} — {{ u.email }}</option>
          </select>
        </div>
        <div class="flex justify-end gap-2">
          <button type="button" class="btn-secondary" @click="showMemberModal = false">Cancel</button>
          <button type="submit" class="btn-primary">Add</button>
        </div>
      </form>
    </Modal>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import http from '../api/http'
import { useAuthStore } from '../stores/auth'
import { useToastStore } from '../stores/toast'
import Spinner from '../components/Spinner.vue'
import Avatar from '../components/Avatar.vue'
import Modal from '../components/Modal.vue'
import Icon from '../components/Icon.vue'

const auth = useAuthStore()
const toast = useToastStore()

const loading = ref(true)
const users = ref([])
const search = ref('')

const showModal = ref(false)
const editing = ref(null)
const form = ref(blank())

function blank() {
  return { name: '', email: '', job_title: '', password: '', is_admin: false }
}

async function load() {
  loading.value = true
  const { data } = await http.get('/users', { params: { search: search.value } })
  users.value = data.data
  loading.value = false
}

function openCreate() {
  editing.value = null
  form.value = blank()
  showModal.value = true
}

function openEdit(u) {
  editing.value = u
  form.value = { name: u.name, email: u.email, job_title: u.job_title || '', password: '', is_admin: u.is_admin }
  showModal.value = true
}

async function save() {
  try {
    if (editing.value) {
      await http.put(`/users/${editing.value.id}`, form.value)
      toast.success('User updated')
    } else {
      await http.post('/users', form.value)
      toast.success('User created')
    }
    showModal.value = false
    load()
  } catch (e) {
    toast.error(e.response?.data?.message || 'Save failed')
  }
}

onMounted(load)
</script>

<template>
  <div>
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
      <h1 class="text-2xl font-semibold text-slate-800">Users</h1>
      <button v-if="auth.user?.is_admin" class="btn-primary" @click="openCreate"><Icon name="user-add" class="h-4 w-4" />Add User</button>
    </div>

    <div class="mb-4 flex gap-3">
      <input v-model="search" class="input max-w-xs" placeholder="Search users…" @keyup.enter="load" />
      <button class="btn-secondary" @click="load">Search</button>
    </div>

    <Spinner v-if="loading" />
    <div v-else class="card divide-y divide-slate-100">
      <div v-for="u in users" :key="u.id" class="flex items-center gap-3 p-4">
        <Avatar :name="u.name" size="lg" />
        <div class="flex-1">
          <p class="flex items-center gap-2 font-medium text-slate-800">
            {{ u.name }}
            <span v-if="u.is_admin" class="rounded-full bg-brand-100 px-2 py-0.5 text-xs font-medium text-brand-700">Admin</span>
          </p>
          <p class="text-sm text-slate-400">{{ u.job_title || '—' }}</p>
        </div>
        <span class="text-sm text-slate-500">{{ u.email }}</span>
        <button v-if="auth.user?.is_admin" class="btn-secondary !py-1 text-xs" @click="openEdit(u)"><Icon name="edit" class="h-4 w-4" />Edit</button>
      </div>
      <p v-if="!users.length" class="p-8 text-center text-slate-400">No users found.</p>
    </div>

    <Modal v-if="showModal" :title="editing ? 'Edit User' : 'Add User'" @close="showModal = false">
      <form class="space-y-4" @submit.prevent="save">
        <div><label class="label">Name</label><input v-model="form.name" class="input" required /></div>
        <div><label class="label">Email</label><input v-model="form.email" type="email" class="input" required /></div>
        <div><label class="label">Job Title</label><input v-model="form.job_title" class="input" /></div>
        <div>
          <label class="label">{{ editing ? 'New Password (leave blank to keep)' : 'Password' }}</label>
          <input v-model="form.password" type="password" class="input" :required="!editing" autocomplete="new-password" />
        </div>
        <label class="flex items-center gap-2 text-sm text-slate-600">
          <input v-model="form.is_admin" type="checkbox" class="h-4 w-4 rounded border-slate-300" />
          Administrator (can add & edit users)
        </label>
        <div class="flex justify-end gap-2">
          <button type="button" class="btn-secondary" @click="showModal = false">Cancel</button>
          <button type="submit" class="btn-primary">Save</button>
        </div>
      </form>
    </Modal>
  </div>
</template>

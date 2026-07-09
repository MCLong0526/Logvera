<script setup>
import { ref, onMounted } from 'vue'
import http from '../api/http'
import Spinner from '../components/Spinner.vue'
import Avatar from '../components/Avatar.vue'

const loading = ref(true)
const users = ref([])
const search = ref('')

async function load() {
  loading.value = true
  const { data } = await http.get('/users', { params: { search: search.value } })
  users.value = data.data
  loading.value = false
}

onMounted(load)
</script>

<template>
  <div>
    <h1 class="mb-5 text-2xl font-semibold text-slate-800">Users</h1>
    <div class="mb-4 flex gap-3">
      <input v-model="search" class="input max-w-xs" placeholder="Search users…" @keyup.enter="load" />
      <button class="btn-secondary" @click="load">Search</button>
    </div>

    <Spinner v-if="loading" />
    <div v-else class="card divide-y divide-slate-100">
      <div v-for="u in users" :key="u.id" class="flex items-center gap-3 p-4">
        <Avatar :name="u.name" size="lg" />
        <div class="flex-1">
          <p class="font-medium text-slate-800">{{ u.name }}</p>
          <p class="text-sm text-slate-400">{{ u.job_title || '—' }}</p>
        </div>
        <span class="text-sm text-slate-500">{{ u.email }}</span>
      </div>
      <p v-if="!users.length" class="p-8 text-center text-slate-400">No users found.</p>
    </div>
  </div>
</template>

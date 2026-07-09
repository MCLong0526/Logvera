<script setup>
import { ref, onMounted, computed } from 'vue'
import { Chart, registerables } from 'chart.js'
import { Doughnut, Bar } from 'vue-chartjs'
import http from '../api/http'
import Spinner from '../components/Spinner.vue'
import Badge from '../components/Badge.vue'
import ProgressBar from '../components/ProgressBar.vue'
import { formatDate } from '../utils/labels'

Chart.register(...registerables)

const loading = ref(true)
const data = ref(null)

const palette = ['#94a3b8', '#3b82f6', '#f59e0b', '#22c55e', '#ef4444']

const cards = computed(() => {
  const c = data.value?.cards || {}
  return [
    { label: 'My Projects', value: c.my_projects, tone: 'text-brand-600' },
    { label: "Today's Tasks", value: c.todays_tasks, tone: 'text-blue-600' },
    { label: 'Overdue Tasks', value: c.overdue_tasks, tone: 'text-red-600' },
    { label: 'Completed Today', value: c.completed_today, tone: 'text-green-600' },
  ]
})

const statusChart = computed(() => ({
  labels: Object.keys(data.value?.charts?.by_status || {}),
  datasets: [{ data: Object.values(data.value?.charts?.by_status || {}), backgroundColor: palette }],
}))

const progressChart = computed(() => ({
  labels: Object.keys(data.value?.charts?.by_progress || {}),
  datasets: [{ label: 'Tasks', data: Object.values(data.value?.charts?.by_progress || {}), backgroundColor: '#6366f1' }],
}))

const projectChart = computed(() => ({
  labels: Object.keys(data.value?.charts?.by_project || {}),
  datasets: [{ label: 'Tasks', data: Object.values(data.value?.charts?.by_project || {}), backgroundColor: '#22c55e' }],
}))

const chartOpts = { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
const doughnutOpts = { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }

onMounted(async () => {
  const res = await http.get('/dashboard')
  data.value = res.data
  loading.value = false
})
</script>

<template>
  <div>
    <h1 class="mb-5 text-2xl font-semibold text-slate-800">Dashboard</h1>
    <Spinner v-if="loading" />
    <template v-else>
      <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div v-for="c in cards" :key="c.label" class="card p-4">
          <p class="text-sm text-slate-500">{{ c.label }}</p>
          <p class="mt-1 text-3xl font-bold" :class="c.tone">{{ c.value }}</p>
        </div>
      </div>

      <div class="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="card p-4">
          <h3 class="mb-3 text-sm font-semibold text-slate-700">Tasks by Status</h3>
          <div class="h-56"><Doughnut :data="statusChart" :options="doughnutOpts" /></div>
        </div>
        <div class="card p-4">
          <h3 class="mb-3 text-sm font-semibold text-slate-700">Tasks by Progress</h3>
          <div class="h-56"><Bar :data="progressChart" :options="chartOpts" /></div>
        </div>
        <div class="card p-4">
          <h3 class="mb-3 text-sm font-semibold text-slate-700">Tasks by Project</h3>
          <div class="h-56"><Bar :data="projectChart" :options="chartOpts" /></div>
        </div>
      </div>

      <div class="card mt-5 p-4">
        <h3 class="mb-3 text-sm font-semibold text-slate-700">Recently Updated Tasks</h3>
        <div class="divide-y divide-slate-100">
          <router-link
            v-for="t in data.recently_updated"
            :key="t.id"
            :to="{ name: 'task', params: { id: t.id } }"
            class="flex items-center gap-3 py-2.5 hover:bg-slate-50"
          >
            <span class="flex-1 truncate text-sm font-medium text-slate-700">{{ t.title }}</span>
            <span class="hidden text-xs text-slate-400 sm:block">{{ t.project?.name }}</span>
            <Badge :value="t.status" />
            <div class="w-28"><ProgressBar :value="t.progress" /></div>
            <span class="hidden w-20 text-right text-xs text-slate-400 md:block">{{ formatDate(t.updated_at) }}</span>
          </router-link>
          <p v-if="!data.recently_updated.length" class="py-6 text-center text-sm text-slate-400">No tasks yet.</p>
        </div>
      </div>
    </template>
  </div>
</template>

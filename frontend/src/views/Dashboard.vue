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
Chart.defaults.font.family = "'JetBrains Mono', monospace"
Chart.defaults.font.size = 11
Chart.defaults.color = '#78716c'

const loading = ref(true)
const data = ref(null)

// Aligned with the status badge dots.
const palette = ['#a8a29e', '#3b82f6', '#f59e0b', '#10b981', '#fb7185']

const cards = computed(() => {
  const c = data.value?.cards || {}
  return [
    { label: 'My Projects', value: c.my_projects, dot: 'bg-ink' },
    { label: "Today's Tasks", value: c.todays_tasks, dot: 'bg-blue-500' },
    { label: 'Overdue Tasks', value: c.overdue_tasks, dot: 'bg-accent' },
    { label: 'Completed Today', value: c.completed_today, dot: 'bg-emerald-500' },
  ]
})

const statusChart = computed(() => ({
  labels: Object.keys(data.value?.charts?.by_status || {}),
  datasets: [{ data: Object.values(data.value?.charts?.by_status || {}), backgroundColor: palette }],
}))

const progressChart = computed(() => ({
  labels: Object.keys(data.value?.charts?.by_progress || {}),
  datasets: [{ label: 'Tasks', data: Object.values(data.value?.charts?.by_progress || {}), backgroundColor: '#1B1A17', borderRadius: 4, maxBarThickness: 44 }],
}))

const projectChart = computed(() => ({
  labels: Object.keys(data.value?.charts?.by_project || {}),
  datasets: [{ label: 'Tasks', data: Object.values(data.value?.charts?.by_project || {}), backgroundColor: '#C2410C', borderRadius: 4, maxBarThickness: 44 }],
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
    <h1 class="mb-6 text-2xl font-bold tracking-tight text-ink">Dashboard</h1>
    <Spinner v-if="loading" />
    <template v-else>
      <div class="stagger grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div v-for="c in cards" :key="c.label" class="card p-5 transition-shadow hover:shadow-lift">
          <div class="flex items-center gap-2">
            <span class="h-1.5 w-1.5 rounded-full" :class="c.dot" />
            <p class="font-mono text-[11px] uppercase tracking-wider text-stone-500">{{ c.label }}</p>
          </div>
          <p class="mt-2 font-mono text-4xl font-semibold tabular-nums text-ink">{{ c.value ?? 0 }}</p>
        </div>
      </div>

      <div class="stagger mt-4 grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="card p-5">
          <h3 class="mb-4 font-mono text-[11px] uppercase tracking-wider text-stone-500">Tasks by Status</h3>
          <div class="h-56"><Doughnut :data="statusChart" :options="doughnutOpts" /></div>
        </div>
        <div class="card p-5">
          <h3 class="mb-4 font-mono text-[11px] uppercase tracking-wider text-stone-500">Tasks by Progress</h3>
          <div class="h-56"><Bar :data="progressChart" :options="chartOpts" /></div>
        </div>
        <div class="card p-5">
          <h3 class="mb-4 font-mono text-[11px] uppercase tracking-wider text-stone-500">Tasks by Project</h3>
          <div class="h-56"><Bar :data="projectChart" :options="chartOpts" /></div>
        </div>
      </div>

      <div class="card mt-4 p-5">
        <h3 class="mb-2 font-mono text-[11px] uppercase tracking-wider text-stone-500">Recently Updated</h3>
        <div class="divide-y divide-line">
          <router-link
            v-for="t in data.recently_updated"
            :key="t.id"
            :to="{ name: 'task', params: { id: t.id } }"
            class="-mx-2 flex items-center gap-3 rounded-md px-2 py-2.5 transition-colors hover:bg-brand-50"
          >
            <span class="flex-1 break-words text-sm font-medium text-ink">{{ t.title }}</span>
            <span class="hidden font-mono text-xs text-stone-400 sm:block">{{ t.project?.name }}</span>
            <Badge :value="t.status" />
            <div class="w-28"><ProgressBar :value="t.progress" /></div>
            <span class="hidden w-20 text-right font-mono text-xs tabular-nums text-stone-400 md:block">{{ formatDate(t.updated_at) }}</span>
          </router-link>
          <p v-if="!data.recently_updated.length" class="py-6 text-center text-sm text-stone-400">No tasks yet.</p>
        </div>
      </div>
    </template>
  </div>
</template>

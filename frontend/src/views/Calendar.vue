<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import http from '../api/http'
import Spinner from '../components/Spinner.vue'
import Avatar from '../components/Avatar.vue'
import Icon from '../components/Icon.vue'

const today = new Date()
const viewYear = ref(today.getFullYear())
const viewMonth = ref(today.getMonth()) // 0-11

const loading = ref(true)
const logs = ref([])
const tasks = ref([])
const projects = ref([])

const WEEKDAYS = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']
const MONTHS = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December']

const monthParam = computed(() => `${viewYear.value}-${String(viewMonth.value + 1).padStart(2, '0')}`)
const monthLabel = computed(() => `${MONTHS[viewMonth.value]} ${viewYear.value}`)

function ymd(d) {
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
}
const todayKey = ymd(today)

// 6-week grid starting on the Monday on/before the 1st.
const weeks = computed(() => {
  const first = new Date(viewYear.value, viewMonth.value, 1)
  const start = new Date(first)
  start.setDate(first.getDate() - ((first.getDay() + 6) % 7))
  const cells = []
  for (let i = 0; i < 42; i++) {
    const d = new Date(start)
    d.setDate(start.getDate() + i)
    cells.push(d)
  }
  const w = []
  for (let i = 0; i < 42; i += 7) w.push(cells.slice(i, i + 7))
  return w
})

// date string -> { users: { id: { name, logs[], tasks[] } }, projects: [] }
const byDay = computed(() => {
  const map = {}
  const userOf = (item) => {
    const day = (map[item.date] ||= { users: {}, projects: [] })
    const uid = item.user?.id ?? 0
    return (day.users[uid] ||= { name: item.user?.name || 'Unknown', logs: [], tasks: [] })
  }
  for (const log of logs.value) userOf(log).logs.push(log)
  for (const t of tasks.value) userOf(t).tasks.push(t)
  for (const p of projects.value) {
    if (!p.due_date) continue
    ;(map[p.due_date] ||= { users: {}, projects: [] }).projects.push(p)
  }
  return map
})

function usersOf(key) {
  return Object.values(byDay.value[key]?.users || {})
}
function projectsOf(key) {
  return byDay.value[key]?.projects || []
}

function prev() {
  if (viewMonth.value === 0) { viewMonth.value = 11; viewYear.value-- } else viewMonth.value--
}
function next() {
  if (viewMonth.value === 11) { viewMonth.value = 0; viewYear.value++ } else viewMonth.value++
}
function goToday() {
  viewYear.value = today.getFullYear()
  viewMonth.value = today.getMonth()
}

async function load() {
  loading.value = true
  try {
    const { data } = await http.get('/calendar', { params: { month: monthParam.value } })
    logs.value = data.logs
    tasks.value = data.tasks || []
    projects.value = data.projects
  } finally {
    loading.value = false
  }
}

watch(monthParam, load)
onMounted(load)
</script>

<template>
  <div>
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-ink">Calendar</h1>
        <p class="text-sm text-stone-500">Daily activity logs and project due dates.</p>
      </div>
      <div class="flex items-center gap-2">
        <button class="btn-secondary !px-2" @click="prev"><Icon name="chevron-left" class="h-4 w-4" /></button>
        <span class="min-w-[10rem] text-center font-mono text-sm font-semibold text-ink">{{ monthLabel }}</span>
        <button class="btn-secondary !px-2" @click="next"><Icon name="chevron-right" class="h-4 w-4" /></button>
        <button class="btn-secondary text-xs" @click="goToday">Today</button>
      </div>
    </div>

    <Spinner v-if="loading" />
    <div v-else class="card p-0">
      <!-- Weekday header -->
      <div class="grid grid-cols-7 border-b border-line bg-brand-50">
        <div v-for="d in WEEKDAYS" :key="d" class="px-2 py-2 text-center font-mono text-[10px] uppercase tracking-widest text-stone-500">{{ d }}</div>
      </div>

      <!-- Weeks -->
      <div v-for="(week, wi) in weeks" :key="wi" class="grid grid-cols-7">
        <div
          v-for="(day, di) in week"
          :key="ymd(day)"
          class="min-h-[7rem] border-b border-r border-line p-1.5 last:border-r-0"
          :class="day.getMonth() === viewMonth ? 'bg-surface' : 'bg-paper/60'"
        >
          <div class="mb-1 flex items-center justify-between">
            <span
              class="inline-flex h-6 w-6 items-center justify-center rounded-full font-mono text-[11px]"
              :class="[
                ymd(day) === todayKey ? 'bg-ink text-paper font-semibold' : 'text-stone-500',
                day.getMonth() === viewMonth ? '' : 'opacity-40',
              ]"
            >{{ day.getDate() }}</span>
          </div>

          <!-- Project due dates -->
          <router-link
            v-for="p in projectsOf(ymd(day))"
            :key="'p' + p.id"
            :to="{ name: 'project', params: { id: p.id } }"
            class="mb-1 flex items-center gap-1 truncate rounded bg-accent/10 px-1.5 py-0.5 font-mono text-[10px] text-accent hover:bg-accent/20"
            :title="`Due: ${p.name}`"
          >
            <Icon name="calendar" class="h-3 w-3 shrink-0" /><span class="truncate">{{ p.name }}</span>
          </router-link>

          <!-- User activity — hover an avatar for that user's logs -->
          <div class="flex flex-wrap gap-1">
            <div v-for="u in usersOf(ymd(day))" :key="u.name" class="group/user relative">
              <span class="relative cursor-default">
                <Avatar :name="u.name" size="sm" />
                <span class="absolute -bottom-1 -right-1 flex h-3.5 min-w-3.5 items-center justify-center rounded-full border border-surface bg-ink px-0.5 font-mono text-[8px] font-medium text-paper">{{ u.logs.length + u.tasks.length }}</span>
              </span>

              <!-- Popup — opens leftward on the last two columns so it doesn't overflow -->
              <div
                class="invisible absolute top-full z-30 mt-1 w-64 scale-95 rounded-lg border border-line bg-surface p-2 opacity-0 shadow-lift transition group-hover/user:visible group-hover/user:scale-100 group-hover/user:opacity-100"
                :class="di >= 5 ? 'right-0 origin-top-right' : 'left-0 origin-top-left'"
              >
                <div class="mb-1.5 flex items-center gap-2 border-b border-line pb-1.5">
                  <Avatar :name="u.name" size="sm" />
                  <span class="text-xs font-semibold text-ink">{{ u.name }}</span>
                  <span class="ml-auto font-mono text-[10px] text-stone-400">
                    <template v-if="u.logs.length">{{ u.logs.length }} log{{ u.logs.length > 1 ? 's' : '' }}</template>
                    <template v-if="u.logs.length && u.tasks.length"> · </template>
                    <template v-if="u.tasks.length">{{ u.tasks.length }} due</template>
                  </span>
                </div>
                <div class="max-h-56 space-y-1 overflow-y-auto">
                  <!-- Tasks due today for this user -->
                  <router-link
                    v-for="t in u.tasks"
                    :key="'t' + t.id"
                    :to="{ name: 'task', params: { id: t.id } }"
                    class="block rounded px-1.5 py-1 text-left hover:bg-brand-50"
                  >
                    <p class="truncate text-[11px] font-medium text-stone-700">
                      <span class="mr-1 rounded bg-accent/10 px-1 font-mono text-[9px] uppercase text-accent">Due</span>{{ t.title }}
                    </p>
                    <p class="truncate font-mono text-[10px] text-stone-400">{{ t.project }}</p>
                  </router-link>
                  <router-link
                    v-for="log in u.logs"
                    :key="log.id"
                    :to="{ name: 'task', params: { id: log.task?.id }, query: { tab: 'updates', log: log.id } }"
                    class="block rounded px-1.5 py-1 text-left hover:bg-brand-50"
                  >
                    <p class="truncate text-[11px] font-medium text-stone-700">{{ log.task?.title || 'Task' }}</p>
                    <p v-if="log.task?.project" class="truncate font-mono text-[10px] text-stone-400">{{ log.task.project }}</p>
                    <p class="line-clamp-2 text-[11px] text-stone-500">{{ log.description }}</p>
                  </router-link>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

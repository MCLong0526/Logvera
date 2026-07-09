<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import http from '../api/http'
import { useAuthStore } from '../stores/auth'
import { useToastStore } from '../stores/toast'
import { useConfirmStore } from '../stores/confirm'
import Spinner from '../components/Spinner.vue'
import Avatar from '../components/Avatar.vue'
import Icon from '../components/Icon.vue'

const route = useRoute()
const auth = useAuthStore()
const toast = useToastStore()
const confirm = useConfirmStore()

const STAGES = [
  { key: 'assigned', label: 'Assigned Task', dot: 'bg-stone-400' },
  { key: 'in_progress', label: 'In Progress', dot: 'bg-blue-500' },
  { key: 'closed', label: 'Close Task', dot: 'bg-emerald-500' },
]

const loading = ref(true)
const board = ref(null)
const cards = ref([])
const newTitle = ref('')
const adding = ref(false)

const columns = computed(() => {
  const map = { assigned: [], in_progress: [], closed: [] }
  for (const c of cards.value) (map[c.stage] || map.assigned).push(c)
  for (const k in map) map[k].sort((a, b) => a.position - b.position)
  return map
})

async function load() {
  loading.value = true
  const { data } = await http.get(`/boards/${route.params.id}`)
  board.value = data.data
  cards.value = data.data.cards
  loading.value = false
}

// --- Quick-add into the Assigned column ---
async function addCard() {
  if (!newTitle.value.trim()) return
  adding.value = true
  try {
    const { data } = await http.post(`/boards/${route.params.id}/cards`, { title: newTitle.value })
    cards.value.push(data.data)
    newTitle.value = ''
  } catch (e) {
    toast.error(e.response?.data?.message || 'Could not add card')
  } finally {
    adding.value = false
  }
}

async function removeCard(card) {
  if (!(await confirm.ask({ title: 'Delete card?', message: card.title }))) return
  try {
    await http.delete(`/boards/${route.params.id}/cards/${card.id}`)
    cards.value = cards.value.filter((c) => c.id !== card.id)
  } catch (e) {
    toast.error(e.response?.data?.message || 'Could not delete card')
  }
}

// --- Native HTML5 drag & drop ---
const dragging = ref(null)
const overStage = ref(null)

function onDragStart(card) { dragging.value = card }
function onDragEnd() { dragging.value = null; overStage.value = null }

// Drop on a card inserts before it; drop on empty column space appends.
function onDrop(stage, targetCard = null) {
  const card = dragging.value
  overStage.value = null
  if (!card || (targetCard && targetCard.id === card.id)) return

  const cols = { assigned: [], in_progress: [], closed: [] }
  for (const s in columns.value) cols[s] = columns.value[s].filter((c) => c.id !== card.id).map((c) => c.id)

  const idx = targetCard ? cols[stage].indexOf(targetCard.id) : -1
  if (idx >= 0) cols[stage].splice(idx, 0, card.id)
  else cols[stage].push(card.id)

  // Optimistic local update, then persist the whole layout.
  card.stage = stage
  for (const s in cols) cols[s].forEach((id, i) => {
    const c = cards.value.find((x) => x.id === id)
    if (c) { c.stage = s; c.position = i }
  })

  http.put(`/boards/${route.params.id}/cards/reorder`, { columns: cols }).catch(() => {
    toast.error('Could not save the move')
    load()
  })
}

onMounted(load)
</script>

<template>
  <Spinner v-if="loading" />
  <div v-else-if="board">
    <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
      <div>
        <router-link :to="{ name: 'taskit' }" class="inline-flex items-center gap-1 text-sm text-stone-400 hover:text-brand-600"><Icon name="back" class="h-4 w-4" />Task It</router-link>
        <h1 class="mt-1 text-2xl font-bold tracking-tight text-ink">{{ board.name }}</h1>
      </div>
      <div class="flex items-center -space-x-1.5">
        <Avatar v-for="m in board.members" :key="m.id" :name="m.name" :src="m.avatar" size="md" class="ring-2 ring-paper" />
      </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
      <div
        v-for="stage in STAGES"
        :key="stage.key"
        class="rounded-lg border bg-brand-50/60 p-3 transition-colors"
        :class="overStage === stage.key ? 'border-ink' : 'border-line'"
        @dragover.prevent="overStage = stage.key"
        @dragleave="overStage === stage.key && (overStage = null)"
        @drop.prevent="onDrop(stage.key)"
      >
        <div class="mb-3 flex items-center gap-2">
          <span class="h-2 w-2 rounded-full" :class="stage.dot" />
          <h2 class="font-mono text-[11px] font-medium uppercase tracking-wider text-stone-500">{{ stage.label }}</h2>
          <span class="ml-auto font-mono text-xs text-stone-400">{{ columns[stage.key].length }}</span>
        </div>

        <!-- Quick-add: new cards always start in Assigned Task -->
        <form v-if="stage.key === 'assigned'" class="mb-2 flex gap-1.5" @submit.prevent="addCard">
          <input v-model="newTitle" class="input !py-1.5 text-sm" placeholder="Add a task…" :disabled="adding" />
          <button type="submit" class="btn-primary !px-2.5 !py-1.5" :disabled="adding"><Icon name="plus" class="h-4 w-4" /></button>
        </form>

        <div class="min-h-[8rem] space-y-2">
          <div
            v-for="card in columns[stage.key]"
            :key="card.id"
            class="card group cursor-grab p-3 active:cursor-grabbing"
            :class="{ 'opacity-40': dragging?.id === card.id }"
            draggable="true"
            @dragstart="onDragStart(card)"
            @dragend="onDragEnd"
            @drop.prevent.stop="onDrop(stage.key, card)"
            @dragover.prevent
          >
            <div class="flex items-start justify-between gap-2">
              <p class="break-words text-sm font-medium text-stone-800">{{ card.title }}</p>
              <button
                v-if="card.creator?.id === auth.user?.id || board.is_owner"
                class="hidden shrink-0 text-stone-300 hover:text-red-500 group-hover:block"
                @click="removeCard(card)"
              ><Icon name="close" class="h-4 w-4" /></button>
            </div>
            <div class="mt-2 flex items-center gap-1.5">
              <Avatar :name="card.creator?.name" :src="card.creator?.avatar" size="sm" />
              <span class="text-xs text-stone-400">{{ card.creator?.name }}</span>
            </div>
          </div>
          <p v-if="!columns[stage.key].length" class="py-6 text-center text-xs text-stone-400">
            {{ stage.key === 'assigned' ? 'No tasks yet.' : 'Drag cards here.' }}
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

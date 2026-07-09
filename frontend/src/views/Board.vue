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
import { formatDate } from '../utils/labels'

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
    <!-- Header — spec-sheet strip -->
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
      <div>
        <router-link :to="{ name: 'taskit' }" class="inline-flex items-center gap-1 text-sm text-stone-400 transition-colors hover:text-accent">
          <Icon name="back" class="h-4 w-4" />Task It
        </router-link>
        <div class="mt-1 flex flex-wrap items-baseline gap-3">
          <h1 class="text-2xl font-bold tracking-tight text-ink">{{ board.name }}</h1>
          <span class="font-mono text-[11px] tracking-wider text-stone-400">BRD-{{ String(board.id).padStart(2, '0') }}</span>
        </div>
        <p class="mt-1 font-mono text-[11px] uppercase tracking-widest text-stone-400">
          {{ cards.length }} cards · opened {{ formatDate(board.created_at) }}
        </p>
      </div>
      <div class="flex items-center gap-3">
        <div class="flex items-center -space-x-1.5">
          <Avatar v-for="m in board.members" :key="m.id" :name="m.name" :src="m.avatar" size="md" class="ring-2 ring-paper" />
        </div>
        <span class="font-mono text-xs text-stone-400">{{ board.members.length }}</span>
      </div>
    </div>

    <!-- Stages — always left → right; scrolls horizontally when narrow -->
    <div class="stagger flex gap-4 overflow-x-auto pb-3">
      <section
        v-for="stage in STAGES"
        :key="stage.key"
        class="flex min-h-[24rem] w-[19rem] shrink-0 flex-col rounded-lg border bg-brand-50/70 transition-all duration-150 md:w-auto md:flex-1"
        :class="overStage === stage.key ? 'border-ink shadow-lift' : 'border-line'"
        @dragover.prevent="overStage = stage.key"
        @dragleave="overStage === stage.key && (overStage = null)"
        @drop.prevent="onDrop(stage.key)"
      >
        <!-- Column header — ledger rule -->
        <header class="flex items-center gap-2 border-b border-line px-3 py-2.5">
          <span class="h-2 w-2 rounded-full" :class="stage.dot" />
          <h2 class="font-mono text-[11px] font-medium uppercase tracking-widest text-stone-500">{{ stage.label }}</h2>
          <span class="ml-auto rounded-full bg-surface px-2 py-0.5 font-mono text-[11px] tabular-nums text-stone-500 ring-1 ring-line">
            {{ String(columns[stage.key].length).padStart(2, '0') }}
          </span>
        </header>

        <div class="flex-1 space-y-2 p-2.5">
          <!-- Quick-add: new cards always start in Assigned Task -->
          <form v-if="stage.key === 'assigned'" class="flex gap-1.5" @submit.prevent="addCard">
            <input v-model="newTitle" class="input !bg-surface !py-1.5 text-sm" placeholder="Add a task…" :disabled="adding" />
            <button type="submit" class="btn-primary !px-2.5 !py-1.5" :disabled="adding" title="Add to Assigned Task">
              <Icon name="plus" class="h-4 w-4" />
            </button>
          </form>

          <TransitionGroup name="kanban">
            <article
              v-for="card in columns[stage.key]"
              :key="card.id"
              class="card group cursor-grab select-none p-3 transition-all duration-150 hover:shadow-lift active:cursor-grabbing"
              :class="{ 'rotate-2 opacity-50 shadow-lift': dragging?.id === card.id }"
              draggable="true"
              @dragstart="onDragStart(card)"
              @dragend="onDragEnd"
              @drop.prevent.stop="onDrop(stage.key, card)"
              @dragover.prevent
            >
              <div class="flex items-start justify-between gap-2">
                <span class="font-mono text-[10px] tracking-wider text-stone-300">#{{ String(card.id).padStart(3, '0') }}</span>
                <button
                  v-if="card.creator?.id === auth.user?.id || board.is_owner"
                  class="hidden shrink-0 text-stone-300 transition-colors hover:text-red-500 group-hover:block"
                  title="Delete card"
                  @click="removeCard(card)"
                ><Icon name="close" class="h-4 w-4" /></button>
              </div>
              <p class="mt-0.5 break-words text-sm font-medium leading-snug text-ink">{{ card.title }}</p>
              <div class="mt-2.5 flex items-center justify-between gap-2 border-t border-line/70 pt-2">
                <span class="flex min-w-0 items-center gap-1.5 text-xs text-stone-400">
                  <Avatar :name="card.creator?.name" :src="card.creator?.avatar" size="sm" />{{ card.creator?.name }}
                </span>
                <span class="shrink-0 whitespace-nowrap font-mono text-[10px] tabular-nums text-stone-300">{{ formatDate(card.created_at) }}</span>
              </div>
            </article>
          </TransitionGroup>

          <!-- Empty drop target -->
          <div
            v-if="!columns[stage.key].length"
            class="rounded-md border-2 border-dashed py-8 text-center font-mono text-[11px] uppercase tracking-widest transition-colors"
            :class="overStage === stage.key ? 'border-ink text-ink' : 'border-line text-stone-300'"
          >
            {{ stage.key === 'assigned' ? 'No tasks yet' : 'Drop cards here' }}
          </div>
        </div>
      </section>
    </div>
  </div>
</template>

<style scoped>
/* Cards glide when a drop reshuffles a column. */
.kanban-move { transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1); }
.kanban-enter-active { transition: opacity 0.2s ease, transform 0.2s ease; }
.kanban-enter-from { opacity: 0; transform: translateY(4px); }
.kanban-leave-active { display: none; }
</style>

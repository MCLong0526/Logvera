<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import http from '../api/http'
import Avatar from '../components/Avatar.vue'
import Icon from '../components/Icon.vue'

const auth = useAuthStore()
const router = useRouter()
const sidebarOpen = ref(false)
const unread = ref(0)

const nav = [
  { name: 'dashboard', label: 'Dashboard', icon: 'dashboard' },
  { name: 'projects', label: 'Projects', icon: 'projects' },
  { name: 'files', label: 'Files', icon: 'files' },
  { name: 'users', label: 'Users', icon: 'users' },
]

async function loadNotifications() {
  try {
    const { data } = await http.get('/notifications')
    unread.value = data.unread_count
  } catch { /* ignore */ }
}

async function logout() {
  await auth.logout()
  router.push({ name: 'login' })
}

onMounted(loadNotifications)
</script>

<template>
  <div class="min-h-screen">
    <!-- Sidebar -->
    <aside
      class="fixed inset-y-0 left-0 z-30 w-60 -translate-x-full border-r border-line bg-surface transition-transform lg:translate-x-0"
      :class="{ 'translate-x-0': sidebarOpen }"
    >
      <div class="flex h-16 items-center gap-2.5 px-5">
        <div class="flex h-8 w-8 items-center justify-center rounded-md bg-ink font-mono text-sm font-bold text-paper">L</div>
        <div class="leading-none">
          <span class="text-[15px] font-bold tracking-tight text-ink">Logvera</span>
          <span class="mt-0.5 block font-mono text-[10px] uppercase tracking-widest text-stone-400">Daily Log</span>
        </div>
      </div>
      <nav class="px-3 py-3">
        <p class="mb-1 px-3 font-mono text-[10px] uppercase tracking-widest text-stone-400">Workspace</p>
        <router-link
          v-for="item in nav"
          :key="item.name"
          :to="{ name: item.name }"
          class="mb-0.5 flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium text-stone-500 transition-colors hover:bg-brand-50 hover:text-ink"
          active-class="!bg-brand-100/70 !text-ink !font-semibold"
          @click="sidebarOpen = false"
        >
          <Icon :name="item.icon" class="h-[18px] w-[18px]" />{{ item.label }}
        </router-link>
      </nav>
    </aside>

    <!-- Main -->
    <div class="lg:pl-60">
      <header class="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-line bg-paper/80 px-5 backdrop-blur">
        <button class="lg:hidden" @click="sidebarOpen = !sidebarOpen"><Icon name="menu" class="h-6 w-6 text-stone-600" /></button>
        <div class="flex-1" />
        <div class="flex items-center gap-4">
          <span v-if="unread" class="relative text-stone-500" title="Unread notifications">
            <Icon name="bell" class="h-[22px] w-[22px]" />
            <span class="absolute -right-1.5 -top-1.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-accent px-1 font-mono text-[10px] font-medium text-white">{{ unread }}</span>
          </span>
          <router-link :to="{ name: 'profile' }" class="flex items-center gap-2 rounded-md py-1 pl-1 pr-2 transition-colors hover:bg-brand-50">
            <Avatar :name="auth.user?.name" size="sm" />
            <span class="hidden text-sm font-medium text-ink sm:block">{{ auth.user?.name }}</span>
          </router-link>
          <button class="btn-secondary !px-2.5 !py-1.5 text-xs" @click="logout"><Icon name="logout" class="h-4 w-4" />Logout</button>
        </div>
      </header>

      <main class="mx-auto max-w-6xl p-6">
        <router-view v-slot="{ Component }">
          <transition name="page" mode="out-in">
            <component :is="Component" :key="$route.fullPath" />
          </transition>
        </router-view>
      </main>
    </div>

    <!-- Mobile backdrop -->
    <div v-if="sidebarOpen" class="fixed inset-0 z-20 bg-black/20 lg:hidden" @click="sidebarOpen = false" />
  </div>
</template>

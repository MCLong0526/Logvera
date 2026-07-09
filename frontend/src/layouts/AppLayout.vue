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
      class="fixed inset-y-0 left-0 z-30 w-60 -translate-x-full border-r border-slate-200 bg-white transition-transform lg:translate-x-0"
      :class="{ 'translate-x-0': sidebarOpen }"
    >
      <div class="flex h-16 items-center gap-2 px-5">
        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-600 font-bold text-white">L</div>
        <span class="text-lg font-semibold text-slate-800">Logvera</span>
      </div>
      <nav class="px-3 py-2">
        <router-link
          v-for="item in nav"
          :key="item.name"
          :to="{ name: item.name }"
          class="mb-1 flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50"
          active-class="!bg-brand-50 !text-brand-700"
          @click="sidebarOpen = false"
        >
          <Icon :name="item.icon" class="h-5 w-5" />{{ item.label }}
        </router-link>
      </nav>
    </aside>

    <!-- Main -->
    <div class="lg:pl-60">
      <header class="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-slate-200 bg-white/80 px-5 backdrop-blur">
        <button class="lg:hidden" @click="sidebarOpen = !sidebarOpen"><Icon name="menu" class="h-6 w-6 text-slate-600" /></button>
        <div class="flex-1" />
        <div class="flex items-center gap-4">
          <span v-if="unread" class="relative text-slate-500" title="Unread notifications">
            <Icon name="bell" class="h-6 w-6" />
            <span class="absolute -right-2 -top-2 rounded-full bg-red-500 px-1.5 text-[10px] text-white">{{ unread }}</span>
          </span>
          <router-link :to="{ name: 'profile' }" class="flex items-center gap-2">
            <Avatar :name="auth.user?.name" size="sm" />
            <span class="hidden text-sm font-medium text-slate-700 sm:block">{{ auth.user?.name }}</span>
          </router-link>
          <button class="btn-secondary !px-2 !py-1 text-xs" @click="logout"><Icon name="logout" class="h-4 w-4" />Logout</button>
        </div>
      </header>

      <main class="mx-auto max-w-6xl p-5">
        <router-view />
      </main>
    </div>

    <!-- Mobile backdrop -->
    <div v-if="sidebarOpen" class="fixed inset-0 z-20 bg-black/20 lg:hidden" @click="sidebarOpen = false" />
  </div>
</template>

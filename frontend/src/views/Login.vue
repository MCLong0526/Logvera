<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useToastStore } from '../stores/toast'

const auth = useAuthStore()
const toast = useToastStore()
const router = useRouter()

const email = ref('admin@logvera.test')
const password = ref('password')
const loading = ref(false)
const error = ref('')

// Decorative "log" lines for the brand panel.
const logLines = [
  { t: '09:04', s: 'completed', m: 'API integration merged' },
  { t: '11:20', s: 'in_progress', m: 'Timeline view — 70%' },
  { t: '14:37', s: 'on_hold', m: 'Awaiting design review' },
  { t: '16:52', s: 'completed', m: 'Dashboard charts shipped' },
]
const dotColor = { completed: 'bg-emerald-400', in_progress: 'bg-blue-400', on_hold: 'bg-amber-400' }

async function submit() {
  loading.value = true
  error.value = ''
  try {
    await auth.login(email.value, password.value)
    router.push({ name: 'dashboard' })
  } catch (e) {
    error.value = e.response?.data?.message || 'Login failed. Check your credentials.'
    toast.error(error.value)
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="grid min-h-screen bg-paper lg:grid-cols-2">
    <!-- Brand panel -->
    <div class="relative hidden overflow-hidden bg-ink p-12 lg:flex lg:flex-col lg:justify-between">
      <!-- fine grid + glow texture -->
      <div class="pointer-events-none absolute inset-0 opacity-[0.06]"
           style="background-image:linear-gradient(#fff 1px,transparent 1px),linear-gradient(90deg,#fff 1px,transparent 1px);background-size:32px 32px" />
      <div class="pointer-events-none absolute -right-24 -top-24 h-96 w-96 rounded-full bg-accent/20 blur-3xl" />

      <div class="relative flex items-center gap-2.5">
        <div class="flex h-9 w-9 items-center justify-center rounded-md bg-paper font-mono text-sm font-bold text-ink">L</div>
        <span class="text-lg font-bold tracking-tight text-paper">Logvera</span>
      </div>

      <div class="relative">
        <p class="mb-3 font-mono text-xs uppercase tracking-[0.2em] text-white/40">Project &amp; Daily Log</p>
        <h1 class="max-w-md text-4xl font-extrabold leading-[1.1] tracking-tight text-paper">
          Every task, every update, logged with intent.
        </h1>

        <div class="mt-8 max-w-sm space-y-2.5 rounded-lg border border-white/10 bg-white/[0.03] p-4">
          <p class="font-mono text-[10px] uppercase tracking-widest text-white/40">Today · timeline</p>
          <div v-for="l in logLines" :key="l.t" class="flex items-center gap-3 text-sm">
            <span class="font-mono text-xs text-white/40">{{ l.t }}</span>
            <span class="h-1.5 w-1.5 rounded-full" :class="dotColor[l.s]" />
            <span class="text-white/80">{{ l.m }}</span>
          </div>
        </div>
      </div>

      <p class="relative font-mono text-xs text-white/30">© 2026 Logvera — built for teams that ship.</p>
    </div>

    <!-- Form panel -->
    <div class="flex items-center justify-center p-6">
      <div class="w-full max-w-sm">
        <div class="mb-8 lg:hidden">
          <div class="flex h-10 w-10 items-center justify-center rounded-md bg-ink font-mono font-bold text-paper">L</div>
        </div>
        <p class="font-mono text-xs uppercase tracking-widest text-stone-400">Welcome back</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-ink">Sign in to Logvera</h2>

        <form class="mt-8 space-y-5" @submit.prevent="submit">
          <div>
            <label class="label">Email</label>
            <input v-model="email" type="email" class="input" required autocomplete="username" />
          </div>
          <div>
            <label class="label">Password</label>
            <input v-model="password" type="password" class="input" required autocomplete="current-password" />
          </div>
          <p v-if="error" class="text-sm text-[#B4322A]">{{ error }}</p>
          <button type="submit" class="btn-primary w-full !py-2.5" :disabled="loading">
            {{ loading ? 'Signing in…' : 'Sign in' }}
          </button>
        </form>

        <div class="mt-6 rounded-md border border-line bg-brand-50 px-3 py-2.5">
          <p class="font-mono text-[11px] text-stone-500">
            <span class="text-stone-400">demo</span> · admin@logvera.test / password
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

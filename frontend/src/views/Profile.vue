<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useToastStore } from '../stores/toast'
import http from '../api/http'
import Avatar from '../components/Avatar.vue'
import Icon from '../components/Icon.vue'

const route = useRoute()
const auth = useAuthStore()
const toast = useToastStore()

const form = ref({
  name: auth.user?.name || '',
  email: auth.user?.email || '',
  job_title: auth.user?.job_title || '',
  password: '',
  password_confirmation: '',
})
const saving = ref(false)
const uploading = ref(false)
const passwordInput = ref(null)

async function onAvatar(e) {
  const file = e.target.files[0]
  if (!file) return
  uploading.value = true
  const fd = new FormData()
  fd.append('avatar', file)
  try {
    const { data } = await http.post('/profile/avatar', fd)
    auth.user = data.data
    toast.success('Profile picture updated')
  } catch (err) {
    toast.error(err.response?.data?.message || 'Upload failed')
  } finally {
    uploading.value = false
    e.target.value = ''
  }
}

// "Change Password" in the header dropdown deep-links here with ?focus=password.
onMounted(() => {
  if (route.query.focus === 'password') passwordInput.value?.focus()
})

async function save() {
  saving.value = true
  const payload = { name: form.value.name, email: form.value.email, job_title: form.value.job_title }
  if (form.value.password) {
    payload.password = form.value.password
    payload.password_confirmation = form.value.password_confirmation
  }
  try {
    await auth.updateProfile(payload)
    form.value.password = ''
    form.value.password_confirmation = ''
    toast.success('Profile updated')
  } catch (e) {
    toast.error(e.response?.data?.message || 'Update failed')
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="max-w-xl">
    <h1 class="mb-5 text-2xl font-bold tracking-tight text-ink">Profile</h1>
    <div class="card p-6">
      <div class="mb-6 flex items-center gap-4">
        <label class="group relative cursor-pointer" title="Change profile picture">
          <Avatar :name="form.name" :src="auth.user?.avatar" size="xl" />
          <span class="absolute inset-0 hidden items-center justify-center rounded-full bg-ink/50 text-paper group-hover:flex">
            <Icon name="camera" class="h-6 w-6" />
          </span>
          <input type="file" accept="image/*" class="hidden" :disabled="uploading" @change="onAvatar" />
        </label>
        <div>
          <p class="text-lg font-semibold text-stone-800">{{ form.name }}</p>
          <p class="text-sm text-stone-400">{{ form.email }}</p>
          <p class="mt-1 font-mono text-[11px] text-stone-400">{{ uploading ? 'Uploading…' : 'Click the photo to change it' }}</p>
        </div>
      </div>
      <form class="space-y-4" @submit.prevent="save">
        <div><label class="label">Name</label><input v-model="form.name" class="input" required /></div>
        <div><label class="label">Email</label><input v-model="form.email" type="email" class="input" required /></div>
        <div><label class="label">Job Title</label><input v-model="form.job_title" class="input" /></div>
        <hr class="border-stone-100" />
        <p class="text-sm text-stone-500">Change password (leave blank to keep current)</p>
        <div class="grid grid-cols-2 gap-3">
          <div><label class="label">New Password</label><input ref="passwordInput" v-model="form.password" type="password" class="input" /></div>
          <div><label class="label">Confirm</label><input v-model="form.password_confirmation" type="password" class="input" /></div>
        </div>
        <button type="submit" class="btn-primary" :disabled="saving">{{ saving ? 'Saving…' : 'Save Changes' }}</button>
      </form>
    </div>
  </div>
</template>

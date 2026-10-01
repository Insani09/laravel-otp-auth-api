<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import RegionCascading from '../components/RegionCascading.vue'
import { user, setUser, clearSession, fetchUser, authHeaders } from '../auth'
import { fireConfirm, fireError, fireSuccess, fireToast } from '../swal'

const router = useRouter()

const pageLoading = ref(!user.value)
const pageError = ref('')

// --- Form profil ---
const name = ref('')
const email = ref('')
const avatarFile = ref(null)
const avatarPreview = ref('')
const savingProfile = ref(false)
const profileMessage = ref('')
const profileOk = ref(false)
const regionRef = ref(null)
const regionReady = ref(false)

// --- Form kata sandi ---
const currentPassword = ref('')
const newPassword = ref('')
const newPasswordConfirmation = ref('')
const savingPassword = ref(false)
const passwordMessage = ref('')
const passwordOk = ref(false)

// --- Hapus akun ---
const deletePassword = ref('')
const deleteConfirmation = ref('')
const deleting = ref(false)
const deleteError = ref('')

function apiError(error) {
  const response = error?.response?.data
  const errors = response?.errors
  if (errors) {
    return Object.values(errors).flat().filter(Boolean).join(' ')
  }
  return response?.message || 'Terjadi kesalahan. Silakan coba lagi.'
}

function applyUser(u) {
  setUser(u)
  name.value = u.name || ''
  email.value = u.email || ''
  avatarPreview.value = u.avatar_url || ''
}

onMounted(async () => {
  const status = await fetchUser()
  if (status === 'guest') {
    clearSession()
    router.replace('/login')
    return
  }
  if (status === 'error') {
    pageError.value = 'Gagal memuat profil. Coba muat ulang halaman.'
    pageLoading.value = false
    return
  }

  applyUser(user.value)

  // Pulihkan dropdown wilayah dari data tersimpan (await, bukan setTimeout).
  if (regionRef.value) {
    try {
      await regionRef.value.restore(user.value)
    } catch (e) {
      console.error('Gagal memulihkan wilayah', e)
    }
    regionReady.value = true
  }

  pageLoading.value = false
})

async function saveProfile() {
  profileMessage.value = ''
  if (!name.value.trim()) {
    profileMessage.value = 'Nama lengkap wajib diisi.'
    profileOk.value = false
    return
  }

  savingProfile.value = true
  try {
    const form = new FormData()
    form.append('name', name.value.trim())
    if (regionRef.value) {
      const payload = regionRef.value.payload()
      Object.entries(payload).forEach(([key, value]) => {
        if (value !== null && value !== undefined && value !== '') {
          form.append(key, value)
        }
      })
    }
    if (avatarFile.value) {
      form.append('avatar', avatarFile.value)
    }

    const response = await window.axios.post('/api/profile', form, {
      headers: { ...authHeaders(), 'Content-Type': 'multipart/form-data' },
    })

    applyUser(response.data.user)
    avatarFile.value = null
    profileMessage.value = response.data.message || 'Profil berhasil diperbarui.'
    profileOk.value = true
    fireToast('success', profileMessage.value)
  } catch (error) {
    profileMessage.value = apiError(error)
    profileOk.value = false
    fireError('Gagal menyimpan', profileMessage.value)
  } finally {
    savingProfile.value = false
  }
}

function onAvatarChange(event) {
  const file = event.target.files?.[0]
  avatarFile.value = file || null
  if (file) {
    avatarPreview.value = URL.createObjectURL(file)
  }
}

async function changePassword() {
  passwordMessage.value = ''
  if (!newPassword.value || newPassword.value !== newPasswordConfirmation.value) {
    passwordMessage.value = 'Konfirmasi kata sandi baru tidak cocok.'
    passwordOk.value = false
    return
  }

  savingPassword.value = true
  try {
    const response = await window.axios.put('/api/profile/password', {
      current_password: currentPassword.value,
      password: newPassword.value,
      password_confirmation: newPasswordConfirmation.value,
    }, { headers: authHeaders() })

    passwordMessage.value = response.data.message || 'Kata sandi berhasil diubah. Silakan login kembali.'
    passwordOk.value = true

    // Server mencabut semua token setelah ganti sandi — wajib login ulang.
    fireSuccess('Sandi berhasil diubah!', 'Silakan login kembali dengan kata sandi baru.').then(() => {
      clearSession()
      router.push('/login')
    })
  } catch (error) {
    passwordMessage.value = apiError(error)
    passwordOk.value = false
  } finally {
    savingPassword.value = false
  }
}

async function destroyAccount() {
  deleteError.value = ''
  if (deleteConfirmation.value !== 'HAPUS AKUN') {
    deleteError.value = 'Ketik HAPUS AKUN untuk melanjutkan.'
    return
  }

  const confirmed = await fireConfirm({
    title: 'Hapus akun permanen?',
    text: 'Akun, avatar, dan semua sesi tidak bisa dikembalikan setelah ini.',
    confirmText: 'Ya, hapus akun saya',
  })
  if (!confirmed) {
    return
  }

  deleting.value = true
  try {
    await window.axios.delete('/api/profile', {
      headers: authHeaders(),
      data: {
        current_password: deletePassword.value,
        confirmation: deleteConfirmation.value,
      },
    })

    fireSuccess('Akun dihapus', 'Semua data kamu telah dibersihkan. Sampai jumpa.')
    clearSession()
    router.push('/login')
  } catch (error) {
    deleteError.value = apiError(error)
    fireError('Gagal menghapus akun', apiError(error))
  } finally {
    deleting.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-slate-950 p-4 text-slate-100 sm:p-8">
    <div class="mx-auto max-w-3xl space-y-6">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-bold text-white">Pengaturan Profil</h1>
        <nav class="flex items-center gap-2 text-sm">
          <router-link to="/dashboard" class="rounded-xl border border-slate-800 px-3 py-2 text-slate-300 transition hover:border-slate-600 hover:text-white">Dashboard</router-link>
          <router-link v-if="user?.role === 'admin'" to="/admin" class="rounded-xl border border-blue-700/50 bg-blue-600/10 px-3 py-2 text-blue-300 transition hover:bg-blue-600/20">Panel Admin</router-link>
        </nav>
      </div>

      <div v-if="pageLoading" class="rounded-2xl border border-slate-800 bg-slate-900 p-6 text-sm text-slate-400">Memuat profil...</div>

      <div v-else-if="pageError" class="rounded-2xl border border-red-900/50 bg-red-950/40 p-6 text-sm text-red-300">{{ pageError }}</div>

      <template v-else>
        <!-- Data profil -->
        <section class="rounded-2xl border border-slate-800 bg-slate-900 p-6 shadow-xl">
          <h2 class="mb-4 text-sm font-bold uppercase tracking-wider text-slate-400">Informasi Pribadi</h2>

          <div v-if="profileMessage" class="mb-4 rounded-xl border p-3 text-center text-xs font-medium" :class="profileOk ? 'border-emerald-900/50 bg-emerald-950/40 text-emerald-300' : 'border-red-900/50 bg-red-950/40 text-red-400'">{{ profileMessage }}</div>

          <div class="mb-5 flex items-center gap-4">
            <img v-if="avatarPreview" :src="avatarPreview" alt="Avatar" class="h-16 w-16 rounded-full border border-slate-700 object-cover" />
            <div v-else class="flex h-16 w-16 items-center justify-center rounded-full border border-slate-700 bg-slate-950 text-xl font-bold text-slate-500">{{ (name || '?').charAt(0).toUpperCase() }}</div>
            <label class="cursor-pointer rounded-xl border border-slate-700 px-3 py-2 text-xs font-semibold text-slate-300 transition hover:border-slate-500 hover:text-white">
              Ganti Avatar
              <input type="file" accept="image/jpeg,image/png,image/webp" class="hidden" @change="onAvatarChange" />
            </label>
          </div>

          <form class="space-y-4" @submit.prevent="saveProfile">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
              <div>
                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-400">Nama Lengkap</label>
                <input v-model="name" type="text" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-4 py-2.5 text-sm text-slate-200 outline-none transition focus:border-blue-600" />
              </div>
              <div>
                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-400">Email</label>
                <input :value="email" type="email" disabled class="w-full cursor-not-allowed rounded-xl border border-slate-800 bg-slate-950 px-4 py-2.5 text-sm text-slate-500 opacity-60" />
              </div>
            </div>

            <!-- Wilayah: dipulihkan otomatis dari data tersimpan -->
            <RegionCascading ref="regionRef" @change="() => {}" />

            <button type="submit" class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-medium text-white shadow-md shadow-blue-900/20 transition hover:bg-blue-500 disabled:cursor-not-allowed disabled:opacity-60" :disabled="savingProfile">
              {{ savingProfile ? 'Menyimpan...' : 'Simpan Profil' }}
            </button>
          </form>
        </section>

        <!-- Ganti kata sandi -->
        <section class="rounded-2xl border border-slate-800 bg-slate-900 p-6 shadow-xl">
          <h2 class="mb-4 text-sm font-bold uppercase tracking-wider text-slate-400">Ganti Kata Sandi</h2>

          <div v-if="passwordMessage" class="mb-4 rounded-xl border p-3 text-center text-xs font-medium" :class="passwordOk ? 'border-emerald-900/50 bg-emerald-950/40 text-emerald-300' : 'border-red-900/50 bg-red-950/40 text-red-400'">{{ passwordMessage }}</div>

          <form class="space-y-4" @submit.prevent="changePassword">
            <div>
              <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-400">Kata Sandi Saat Ini</label>
              <input v-model="currentPassword" type="password" autocomplete="current-password" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-4 py-2.5 text-sm text-slate-200 outline-none transition focus:border-blue-600" />
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
              <div>
                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-400">Kata Sandi Baru</label>
                <input v-model="newPassword" type="password" autocomplete="new-password" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-4 py-2.5 text-sm text-slate-200 outline-none transition focus:border-blue-600" placeholder="Min. 12 karakter, huruf + angka" />
              </div>
              <div>
                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-400">Konfirmasi Sandi Baru</label>
                <input v-model="newPasswordConfirmation" type="password" autocomplete="new-password" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-4 py-2.5 text-sm text-slate-200 outline-none transition focus:border-blue-600" placeholder="Ulangi sandi baru" />
              </div>
            </div>

            <button type="submit" class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-medium text-white shadow-md shadow-blue-900/20 transition hover:bg-blue-500 disabled:cursor-not-allowed disabled:opacity-60" :disabled="savingPassword">
              {{ savingPassword ? 'Memproses...' : 'Ubah Kata Sandi' }}
            </button>
          </form>
        </section>

        <!-- Zona bahaya -->
        <section class="rounded-2xl border border-red-900/50 bg-red-950/20 p-6 shadow-xl">
          <h2 class="mb-4 text-sm font-bold uppercase tracking-wider text-red-400">Hapus Akun</h2>
          <p class="mb-4 text-xs text-slate-400">Tindakan permanen: akun, avatar, dan semua sesi akan dihapus. Ketik <span class="font-mono font-semibold text-red-300">HAPUS AKUN</span> untuk konfirmasi.</p>

          <div v-if="deleteError" class="mb-4 rounded-xl border border-red-900/50 bg-red-950/40 p-3 text-center text-xs font-medium text-red-400">{{ deleteError }}</div>

          <form class="grid grid-cols-1 gap-4 sm:grid-cols-2" @submit.prevent="destroyAccount">
            <div>
              <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-400">Kata Sandi Saat Ini</label>
              <input v-model="deletePassword" type="password" autocomplete="current-password" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-4 py-2.5 text-sm text-slate-200 outline-none transition focus:border-red-600" />
            </div>
            <div>
              <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-400">Konfirmasi</label>
              <input v-model="deleteConfirmation" type="text" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-4 py-2.5 text-sm text-slate-200 outline-none transition focus:border-red-600" placeholder="HAPUS AKUN" />
            </div>

            <div class="sm:col-span-2">
              <button type="submit" class="rounded-xl bg-red-600 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-red-500 disabled:cursor-not-allowed disabled:opacity-60" :disabled="deleting">
                {{ deleting ? 'Menghapus...' : 'Hapus Akun Permanen' }}
              </button>
            </div>
          </form>
        </section>
      </template>
    </div>
  </div>
</template>

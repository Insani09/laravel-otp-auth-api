<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { user, isAdmin, clearSession, authHeaders } from '../auth'
import { fireConfirm, fireToast } from '../swal'

const router = useRouter()
const loading = ref(!user.value)
const errorMessage = ref('')

function handleSessionExpired() {
  clearSession()
  router.push('/login')
}

async function handleLogout() {
  const confirmed = await fireConfirm({
    title: 'Keluar dari akun?',
    text: 'Sesi kamu akan diakhiri.',
    confirmText: 'Ya, keluar',
    icon: 'question',
  })
  if (!confirmed) {
    return
  }

  try {
    await window.axios.post('/api/logout', {}, { headers: authHeaders() })
  } catch (error) {
    console.warn('Logout gagal:', error?.response?.data?.message || error.message)
  } finally {
    clearSession()
    fireToast('success', 'Sampai jumpa!')
    router.push('/login')
  }
}
</script>

<template>
  <div class="min-h-screen bg-slate-950 p-4 text-slate-100 sm:p-8">
    <div class="mx-auto max-w-4xl rounded-2xl border border-slate-800 bg-slate-900 p-6 shadow-xl">
      <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 class="text-2xl font-bold text-white">Dashboard</h1>
          <p class="mt-1 text-sm text-slate-400">Ringkasan akun dan status sesi Anda.</p>
        </div>
        <nav class="flex items-center gap-2 text-sm">
          <router-link to="/profile" class="rounded-xl border border-slate-800 px-3 py-2 text-slate-300 transition hover:border-slate-600 hover:text-white">Profil</router-link>
          <router-link v-if="isAdmin()" to="/admin" class="rounded-xl border border-blue-700/50 bg-blue-600/10 px-3 py-2 text-blue-300 transition hover:bg-blue-600/20">Panel Admin</router-link>
        </nav>
      </div>

      <div v-if="loading" class="rounded-xl border border-slate-800 bg-slate-950 p-4 text-sm text-slate-400">Memuat data sesi...</div>

      <div v-else-if="user" class="mb-6 space-y-2 rounded-xl border border-slate-800 bg-slate-950 p-4 text-sm">
        <p class="text-slate-400">Sedang login sebagai:</p>
        <p class="text-lg font-semibold text-white">{{ user.name }}</p>
        <p class="text-xs text-slate-500">{{ user.email }}</p>
        <p class="pt-1 text-xs text-slate-400">
          Role: <span class="font-semibold text-emerald-400">{{ user.role }}</span>
          <span v-if="user.negara"> · Wilayah: <span class="uppercase">{{ [user.kota, user.provinsi, user.negara].filter(Boolean).join(', ') }}</span></span>
        </p>
      </div>

      <div v-else class="mb-6 rounded-xl border border-red-900/50 bg-red-950/40 p-4 text-sm text-red-300">
        Sesi tidak ditemukan.
        <button type="button" class="font-semibold underline" @click="handleSessionExpired">Silakan login kembali</button>
        <span v-if="errorMessage" class="mt-1 block text-xs text-red-400">{{ errorMessage }}</span>
      </div>

      <button type="button" class="rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-500" @click="handleLogout">
        Keluar
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { user, isAdmin, clearSession, authHeaders } from '../../auth'
import UserForm from './UserForm.vue'
import { fireConfirm, fireError, fireSuccess, fireToast } from '../../swal'

const router = useRouter()

const users = ref([])
const stats = ref({ total: 0, admin: 0, user: 0 })
const loading = ref(true)
const errorMessage = ref('')
const notice = ref('')

// Filter & pagination
const search = ref('')
const roleFilter = ref('')
const perPage = ref(5)
const currentPage = ref(1)
const lastPage = ref(1)
const total = ref(0)

// Modal
const showModal = ref(false)
const editingUser = ref(null) // null = tambah, object = edit
let fetchSeq = 0

const showingFrom = computed(() => (total.value === 0 ? 0 : (currentPage.value - 1) * perPage.value + 1))
const showingTo = computed(() => Math.min(currentPage.value * perPage.value, total.value))

// DB menyimpan UTC; tampilkan dalam zona waktu lokal viewer (mis. WIB).
// Format diminta owner: 2026-09-23 08.00
function formatTanggal(iso) {
  if (!iso) return '—'
  const date = new Date(iso)
  if (Number.isNaN(date.getTime())) return '—'

  const pad = (n) => String(n).padStart(2, '0')
  const tanggal = `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`
  const jam = `${pad(date.getHours())}.${pad(date.getMinutes())}`
  return `${tanggal} ${jam}`
}

// Waktu relatif ala medsos; kini tampil sebagai tooltip di atas tanggal-jam.
function waktuRelatif(iso) {
  if (!iso) return '—'
  const date = new Date(iso)
  if (Number.isNaN(date.getTime())) return '—'

  const detik = Math.floor((Date.now() - date.getTime()) / 1000)
  if (detik < 60) return 'baru saja'
  const menit = Math.floor(detik / 60)
  if (menit < 60) return `${menit} menit lalu`
  const jam = Math.floor(menit / 60)
  if (jam < 24) return `${jam} jam lalu`
  const hari = Math.floor(jam / 24)
  if (hari < 7) return `${hari} hari lalu`
  if (hari < 30) return `${Math.floor(hari / 7)} minggu lalu`
  if (hari < 365) return `${Math.floor(hari / 30)} bulan lalu`
  return `${Math.floor(hari / 365)} tahun lalu`
}

async function loadUsers() {
  const seq = ++fetchSeq
  loading.value = true
  errorMessage.value = ''
  try {
    const params = new URLSearchParams()
    if (search.value.trim()) params.set('search', search.value.trim())
    if (roleFilter.value) params.set('role', roleFilter.value)
    params.set('page', String(currentPage.value))
    params.set('per_page', String(perPage.value))

    const response = await window.axios.get(`/api/admin/users?${params.toString()}`, { headers: authHeaders() })

    if (seq !== fetchSeq) return // respons lama diabaikan

    users.value = response.data.data || []
    stats.value = response.data.stats || { total: 0, admin: 0, user: 0 }
    currentPage.value = response.data.current_page || 1
    lastPage.value = response.data.last_page || 1
    total.value = response.data.total || 0
  } catch (error) {
    if (seq !== fetchSeq) return
    errorMessage.value = error?.response?.data?.message || 'Gagal memuat daftar pengguna.'
  } finally {
    if (seq === fetchSeq) loading.value = false
  }
}

function applyFilters() {
  currentPage.value = 1
  loadUsers()
}

function goToPage(page) {
  if (page >= 1 && page <= lastPage.value) {
    currentPage.value = page
    loadUsers()
  }
}

function openCreate() {
  editingUser.value = null
  showModal.value = true
}

function openEdit(userData) {
  editingUser.value = userData
  showModal.value = true
}

async function onSaved(payload) {
  showModal.value = false
  fireSuccess('Berhasil!', payload?.message || 'Data pengguna tersimpan.')
  loadUsers()
}

async function deleteUser(userData) {
  const confirmed = await fireConfirm({
    title: `Hapus ${userData.name}?`,
    text: 'Akun, avatar, dan semua sesinya akan dihapus permanen.',
    confirmText: 'Ya, hapus',
  })
  if (!confirmed) {
    return
  }

  try {
    const response = await window.axios.delete(`/api/admin/users/${userData.id}`, { headers: authHeaders() })
    fireSuccess('Terhapus!', response.data.message || 'Pengguna berhasil dihapus.')
    loadUsers()
  } catch (error) {
    fireError('Gagal menghapus', error?.response?.data?.message || 'Coba lagi beberapa saat.')
  }
}

async function handleLogout() {
  const confirmed = await fireConfirm({
    title: 'Keluar dari akun?',
    text: 'Kamu harus login lagi untuk mengakses panel admin.',
    confirmText: 'Ya, keluar',
    icon: 'question',
  })
  if (!confirmed) {
    return
  }

  try {
    await window.axios.post('/api/logout', {}, { headers: authHeaders() })
  } catch (error) {
    console.warn('Logout gagal:', error?.message)
  } finally {
    clearSession()
    fireToast('success', 'Sampai jumpa!')
    router.push('/login')
  }
}

onMounted(() => {
  if (!user.value) {
    // Guard router sudah menolak akses tanpa sesi; ini pengaman tambahan.
    router.replace('/login')
    return
  }
  loadUsers()
})
</script>

<template>
  <div class="min-h-screen bg-slate-950 p-4 text-slate-100 sm:p-8">
    <div class="mx-auto max-w-6xl space-y-6">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 class="text-2xl font-bold text-white">Manajemen Pengguna</h1>
          <p class="mt-1 text-sm text-slate-400">Kelola akun, peran, dan wilayah domisili seluruh pengguna.</p>
        </div>
        <nav class="flex items-center gap-2 text-sm">
          <router-link to="/dashboard" class="rounded-xl border border-slate-800 px-3 py-2 text-slate-300 transition hover:border-slate-600 hover:text-white">Dashboard</router-link>
          <router-link to="/profile" class="rounded-xl border border-slate-800 px-3 py-2 text-slate-300 transition hover:border-slate-600 hover:text-white">Profil</router-link>
          <button type="button" class="rounded-xl bg-red-600 px-3 py-2 font-semibold text-white transition hover:bg-red-500" @click="handleLogout">Keluar</button>
        </nav>
      </div>

      <!-- Statistik -->
      <div class="grid grid-cols-3 gap-3">
        <div class="rounded-2xl border border-slate-800 bg-slate-900 p-4 text-center shadow-xl">
          <p class="text-2xl font-bold text-white">{{ stats.total }}</p>
          <p class="text-xs uppercase tracking-wider text-slate-400">Total Pengguna</p>
        </div>
        <div class="rounded-2xl border border-blue-900/50 bg-blue-950/30 p-4 text-center shadow-xl">
          <p class="text-2xl font-bold text-blue-300">{{ stats.admin }}</p>
          <p class="text-xs uppercase tracking-wider text-blue-400/70">Admin</p>
        </div>
        <div class="rounded-2xl border border-emerald-900/50 bg-emerald-950/30 p-4 text-center shadow-xl">
          <p class="text-2xl font-bold text-emerald-300">{{ stats.user }}</p>
          <p class="text-xs uppercase tracking-wider text-emerald-400/70">User</p>
        </div>
      </div>

      <div v-if="notice" class="rounded-xl border border-emerald-900/50 bg-emerald-950/40 p-3 text-center text-xs font-medium text-emerald-300">{{ notice }}</div>
      <div v-if="errorMessage" class="rounded-xl border border-red-900/50 bg-red-950/40 p-3 text-center text-xs font-medium text-red-400">{{ errorMessage }}</div>

      <!-- Filter + tabel -->
      <section class="rounded-2xl border border-slate-800 bg-slate-900 p-4 shadow-xl sm:p-6">
        <div class="mb-4 flex flex-wrap items-center gap-3">
          <input
            v-model="search"
            type="search"
            placeholder="Cari nama, email, wilayah..."
            class="min-w-0 flex-1 rounded-xl border border-slate-800 bg-slate-950 px-4 py-2.5 text-sm text-slate-200 outline-none transition focus:border-blue-600"
            @keyup.enter="applyFilters"
          />
          <select v-model="roleFilter" class="rounded-xl border border-slate-800 bg-slate-950 px-3 py-2.5 text-sm text-slate-200 outline-none focus:border-blue-600" @change="applyFilters">
            <option value="">Semua Role</option>
            <option value="admin">Admin</option>
            <option value="user">User</option>
          </select>
          <select v-model.number="perPage" class="rounded-xl border border-slate-800 bg-slate-950 px-3 py-2.5 text-sm text-slate-200 outline-none focus:border-blue-600" @change="applyFilters">
            <option :value="5">5 / halaman</option>
            <option :value="10">10 / halaman</option>
            <option :value="25">25 / halaman</option>
            <option :value="50">50 / halaman</option>
          </select>
          <button type="button" class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-500" @click="openCreate">+ Tambah Pengguna</button>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm">
            <thead>
              <tr class="border-b border-slate-800 text-xs uppercase tracking-wider text-slate-500">
                <th class="py-3 pr-4">Pengguna</th>
                <th class="py-3 pr-4">Role</th>
                <th class="py-3 pr-4">Wilayah</th>
                <th class="py-3 pr-4">Terdaftar</th>
                <th class="py-3 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="loading">
                <td colspan="5" class="py-8 text-center text-slate-400">Memuat data pengguna...</td>
              </tr>
              <tr v-else-if="users.length === 0">
                <td colspan="5" class="py-8 text-center text-slate-400">Tidak ada pengguna yang cocok.</td>
              </tr>
              <tr v-for="u in users" v-else :key="u.id" class="border-b border-slate-800/60 transition hover:bg-slate-800/30">
                <td class="py-3 pr-4">
                  <div class="flex items-center gap-3">
                    <img v-if="u.avatar_url" :src="u.avatar_url" alt="" class="h-9 w-9 rounded-full border border-slate-700 object-cover" />
                    <div v-else class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-800 text-xs font-bold text-slate-400">{{ (u.name || '?').charAt(0).toUpperCase() }}</div>
                    <div>
                      <p class="font-semibold text-white">{{ u.name }}</p>
                      <p class="text-xs text-slate-500">{{ u.email }}</p>
                    </div>
                  </div>
                </td>
                <td class="py-3 pr-4">
                  <span class="rounded-full px-2 py-1 text-xs font-semibold" :class="u.role === 'admin' ? 'bg-blue-950 text-blue-300' : 'bg-slate-800 text-slate-300'">{{ u.role }}</span>
                </td>
                <td class="py-3 pr-4 text-xs uppercase text-slate-400">{{ [u.kota, u.provinsi, u.negara].filter(Boolean).join(', ') || '—' }}</td>
                <td class="cursor-help py-3 pr-4 text-xs text-slate-500" :title="waktuRelatif(u.created_at)">{{ formatTanggal(u.created_at) }}</td>
                <td class="py-3 text-right">
                  <button type="button" class="rounded-lg border border-slate-700 px-3 py-1.5 text-xs font-semibold text-slate-300 transition hover:border-blue-600 hover:text-blue-300" @click="openEdit(u)">Edit</button>
                  <button type="button" class="ml-2 rounded-lg border border-red-900/60 px-3 py-1.5 text-xs font-semibold text-red-400 transition hover:bg-red-950/50" @click="deleteUser(u)">Hapus</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination: panah muncul hanya saat data melebihi satu halaman -->
        <div v-if="lastPage > 1" class="mt-4 flex items-center justify-between text-xs text-slate-400">
          <span>Menampilkan {{ showingFrom }}–{{ showingTo }} dari {{ total }} pengguna</span>
          <div class="flex items-center gap-2">
            <button
              type="button"
              aria-label="Halaman sebelumnya"
              class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-800 text-slate-300 transition hover:border-blue-600 hover:text-blue-300 disabled:cursor-not-allowed disabled:opacity-40"
              :disabled="currentPage <= 1"
              @click="goToPage(currentPage - 1)"
            >
              <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
            </button>
            <span class="font-semibold text-slate-300">Hal. {{ currentPage }} / {{ lastPage }}</span>
            <button
              type="button"
              aria-label="Halaman berikutnya"
              class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-800 text-slate-300 transition hover:border-blue-600 hover:text-blue-300 disabled:cursor-not-allowed disabled:opacity-40"
              :disabled="currentPage >= lastPage"
              @click="goToPage(currentPage + 1)"
            >
              <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
            </button>
          </div>
        </div>
      </section>

      <!-- Modal tambah/edit -->
      <UserForm v-if="showModal" :user="editingUser" @close="showModal = false" @saved="onSaved" />
    </div>
  </div>
</template>

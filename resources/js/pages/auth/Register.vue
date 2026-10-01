<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import RegionCascading from '../../components/RegionCascading.vue'
import { fireSuccess } from '../../swal'

const router = useRouter()

const name = ref('')
const email = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const showPassword = ref(false)
const showConfirmation = ref(false)
const loading = ref(false)
const message = ref('')
const messageType = ref('error')

// Payload wilayah dari komponen bersama (sudah selaras dengan validasi backend).
const regionPayload = ref(null)

function onRegionChange(payload) {
  regionPayload.value = payload
}

function showMessage(text, type = 'error') {
  message.value = text
  messageType.value = type
}

function clearMessage() {
  message.value = ''
}

function apiError(error) {
  const response = error?.response?.data
  const errors = response?.errors
  if (errors) {
    return Object.values(errors).flat().filter(Boolean).join(' ')
  }
  return response?.message || 'Terjadi kesalahan. Silakan coba lagi.'
}

async function register() {
  clearMessage()
  if (!name.value || !email.value || !password.value || !passwordConfirmation.value) {
    showMessage('Semua kolom data akun wajib diisi.')
    return
  }
  if (password.value !== passwordConfirmation.value) {
    showMessage('Konfirmasi kata sandi tidak cocok.')
    return
  }

  loading.value = true
  try {
    const response = await window.axios.post('/auth/register', {
      name: name.value.trim(),
      email: email.value.trim(),
      password: password.value,
      password_confirmation: passwordConfirmation.value,
      ...regionPayload.value,
    })

    fireSuccess('Registrasi berhasil!', 'Akun kamu sudah dibuat. Silakan masuk sekarang.').then(() => {
      router.push('/login')
    })
  } catch (error) {
    showMessage(apiError(error))
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <main class="min-h-screen bg-slate-950 px-4 py-10 text-slate-100 selection:bg-emerald-600 selection:text-white">
    <section class="mx-auto w-full max-w-2xl rounded-2xl border border-slate-800 bg-slate-900/80 p-6 shadow-2xl backdrop-blur-md sm:p-8">
      <header class="mb-6 text-center">
        <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-xl border border-emerald-500/20 bg-emerald-600/10 text-emerald-400">
          <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" /></svg>
        </div>
        <h1 class="text-xl font-bold tracking-tight text-white">Pendaftaran Akun</h1>
        <p class="mt-1 text-xs text-slate-400">Lengkapi data diri dan informasi domisili untuk membuat akun</p>
      </header>

      <div v-if="message" class="mb-4 rounded-xl border p-3 text-center text-xs font-medium" :class="messageType === 'success' ? 'border-emerald-900/50 bg-emerald-950/40 text-emerald-300' : 'border-red-900/50 bg-red-950/40 text-red-400'">{{ message }}</div>

      <form class="space-y-4" @submit.prevent="register">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-400">Nama Lengkap</label>
            <input v-model="name" type="text" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-4 py-2.5 text-sm text-slate-200 outline-none transition focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600" placeholder="Nama Anda" />
          </div>
          <div>
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-400">Alamat Email</label>
            <input v-model="email" type="email" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-4 py-2.5 text-sm text-slate-200 outline-none transition focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600" placeholder="nama@gmail.com" />
          </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-400">Kata Sandi</label>
            <div class="relative">
              <input v-model="password" :type="showPassword ? 'text' : 'password'" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-4 py-2.5 pr-11 text-sm text-slate-200 outline-none transition focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600" placeholder="Min. 12 karakter, huruf + angka, tanpa spasi" autocomplete="new-password" />
              <button type="button" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-500 transition hover:text-slate-300" :title="showPassword ? 'Sembunyikan sandi' : 'Lihat sandi'" :aria-label="showPassword ? 'Sembunyikan sandi' : 'Lihat sandi'" @click="showPassword = !showPassword">
                <svg v-if="showPassword" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                </svg>
                <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                </svg>
              </button>
            </div>
          </div>
          <div>
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-400">Konfirmasi Sandi</label>
            <div class="relative">
              <input v-model="passwordConfirmation" :type="showConfirmation ? 'text' : 'password'" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-4 py-2.5 pr-11 text-sm text-slate-200 outline-none transition focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600" placeholder="Ulangi sandi" autocomplete="new-password" />
              <button type="button" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-500 transition hover:text-slate-300" :title="showConfirmation ? 'Sembunyikan sandi' : 'Lihat sandi'" :aria-label="showConfirmation ? 'Sembunyikan sandi' : 'Lihat sandi'" @click="showConfirmation = !showConfirmation">
                <svg v-if="showConfirmation" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                </svg>
                <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                </svg>
              </button>
            </div>
          </div>
        </div>

        <hr class="my-4 border-slate-800" />

        <!-- Pemilihan wilayah: komponen bersama (dipakai juga oleh Profile & Admin SPA) -->
        <RegionCascading @change="onRegionChange" />

        <div class="flex items-center justify-end pt-2 text-xs">
          <router-link to="/login" class="text-blue-400 hover:underline">Sudah punya akun? Masuk</router-link>
        </div>

        <button type="submit" class="w-full rounded-xl bg-emerald-600 py-2.5 text-sm font-medium text-white shadow-md shadow-emerald-900/20 transition hover:bg-emerald-500 disabled:cursor-not-allowed disabled:opacity-60" :disabled="loading">
          {{ loading ? 'Memproses...' : 'Daftar Sekarang' }}
        </button>
      </form>
    </section>
  </main>
</template>

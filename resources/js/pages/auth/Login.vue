<script setup>
import { computed, onBeforeUnmount, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { setSession } from '../../auth'
import { fireSuccess, fireToast } from '../../swal'

const route = useRoute()
const router = useRouter()

const panel = ref('login') // 'login' | 'forgot'
const mode = ref('password') // 'password' | 'otp'
const loading = ref(false)
const showPassword = ref(false)
const email = ref('')
const password = ref('')
const remember = ref(false)
const otp = ref('')
const otpSent = ref(false)
const otpSeconds = ref(0)
const otpTimer = ref(null)
const message = ref('')
const messageType = ref('error')

// Form lupa kata sandi (3 langkah: email → verifikasi OTP → sandi baru)
const forgotStep = ref(1) // 1: minta OTP, 2: verifikasi OTP, 3: sandi baru
const forgotEmail = ref('')
const forgotOtp = ref('')
const newPassword = ref('')
const newPasswordConfirmation = ref('')

const formattedOtpTime = computed(() => {
  const minutes = Math.floor(otpSeconds.value / 60)          
  const seconds = otpSeconds.value % 60
  return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`
})
const otpExpired = computed(() => otpSent.value && otpSeconds.value <= 0)

function showMessage(text, type = 'error') {
  message.value = text
  messageType.value = type
  if (type === 'success') {
    fireToast('success', text)
  }
}

function clearMessage() {
  message.value = ''
}

function stopOtpTimer() {
  if (otpTimer.value) {
    window.clearInterval(otpTimer.value)
    otpTimer.value = null
  }
}

function startOtpTimer(seconds = 300) {
  stopOtpTimer()
  otpSeconds.value = seconds
  otpTimer.value = window.setInterval(() => {
    otpSeconds.value -= 1
    if (otpSeconds.value <= 0) {
      stopOtpTimer()
      showMessage('Kode OTP telah kedaluwarsa. Silakan kirim ulang kode.')
    }
  }, 1000)
}

function apiError(error) {
  const response = error?.response?.data
  const errors = response?.errors
  if (errors) {
    return Object.values(errors).flat().filter(Boolean).join(' ')
  }
  return response?.message || 'Terjadi kesalahan. Silakan coba lagi.'
}

function homeFor(userData) {
  return userData?.role === 'admin' ? '/admin' : '/dashboard'
}

function finishLogin(response) {
  if (response.data.token) {
    setSession(response.data.token, response.data.user || null)
  }
  showMessage(response.data.message || 'Login berhasil.', 'success')

  const target = typeof route.query.redirect === 'string' && route.query.redirect.startsWith('/')
    ? route.query.redirect
    : homeFor(response.data.user)

  router.push(target)
}

async function loginWithPassword() {
  clearMessage()
  if (!email.value || !password.value) {
    showMessage('Alamat email dan kata sandi wajib diisi.')
    return
  }

  loading.value = true
  try {
    const response = await window.axios.post('/auth/login', {
      email: email.value.trim(),
      password: password.value,
      remember: remember.value ? 1 : 0,
    })
    finishLogin(response)
  } catch (error) {
    showMessage(apiError(error))
  } finally {
    loading.value = false
  }
}

async function sendOtp() {
  clearMessage()
  if (!email.value) {
    showMessage('Alamat email wajib diisi dengan format yang benar.')
    return
  }

  loading.value = true
  try {
    const response = await window.axios.post('/auth/otp/send', { email: email.value.trim() })
    otpSent.value = true
    otp.value = ''
    startOtpTimer(response.data.expires_in || 300)
    showMessage(response.data.message || 'Kode OTP telah dikirim ke email Anda.', 'success')
  } catch (error) {
    showMessage(apiError(error))
  } finally {
    loading.value = false
  }
}

async function verifyOtp() {
  clearMessage()
  if (!/^\d{6}$/.test(otp.value)) {
    showMessage('Kode OTP harus berupa 6 digit angka.')
    return
  }
  if (otpExpired.value) {
    showMessage('Kode OTP telah kedaluwarsa. Silakan kirim ulang kode.')
    return
  }

  loading.value = true
  try {
    const response = await window.axios.post('/auth/otp/verify', {
      email: email.value.trim(),
      otp: otp.value,
      remember: remember.value ? 1 : 0,
    })
    stopOtpTimer()
    finishLogin(response)
  } catch (error) {
    showMessage(apiError(error))
  } finally {
    loading.value = false
  }
}

// --- Lupa kata sandi ---

// Langkah 2: verifikasi kode dulu (tanpa mengubah apa pun), baru buka form sandi.
async function verifyResetOtp() {
  clearMessage()
  if (!/^\d{6}$/.test(forgotOtp.value)) {
    showMessage('Kode OTP harus berupa 6 digit angka.')
    return
  }

  loading.value = true
  try {
    const response = await window.axios.post('/api/verify-reset-otp', {
      email: forgotEmail.value.trim(),
      otp: forgotOtp.value,
    })
    forgotStep.value = 3
    fireSuccess('Kode terverifikasi!', 'Silakan buat kata sandi baru Anda.')
  } catch (error) {
    showMessage(apiError(error))
  } finally {
    loading.value = false
  }
}

async function requestResetOtp() {
  clearMessage()
  if (!forgotEmail.value) {
    showMessage('Masukkan alamat email terdaftar Anda.')
    return
  }

  loading.value = true
  try {
    const response = await window.axios.post('/auth/forgot-password', { email: forgotEmail.value.trim() })
    forgotStep.value = 2
    startOtpTimer(response.data.expires_in || 300)
    showMessage(response.data.message || 'Kode OTP reset telah dikirim.', 'success')
  } catch (error) {
    showMessage(apiError(error))
  } finally {
    loading.value = false
  }
}

async function submitResetPassword() {
  clearMessage()
  if (!/^\d{6}$/.test(forgotOtp.value)) {
    showMessage('Kode OTP harus berupa 6 digit angka.')
    return
  }
  if (!newPassword.value || newPassword.value !== newPasswordConfirmation.value) {
    showMessage('Konfirmasi kata sandi baru tidak cocok.')
    return
  }

  loading.value = true
  try {
    const response = await window.axios.post('/auth/reset-password', {
      email: forgotEmail.value.trim(),
      otp: forgotOtp.value,
      password: newPassword.value,
      password_confirmation: newPasswordConfirmation.value,
    })
    stopOtpTimer()
    fireSuccess('Kata sandi berhasil diubah!', 'Silakan masuk dengan kata sandi baru Anda.').then(() => {
      panel.value = 'login'
      mode.value = 'password'
      email.value = forgotEmail.value
      password.value = ''
    })
  } catch (error) {
    showMessage(apiError(error))
  } finally {
    loading.value = false
  }
}

function switchMode(nextMode) {
  mode.value = nextMode
  clearMessage()
  if (nextMode === 'password') {
    otpSent.value = false
    stopOtpTimer()
  }
}

onBeforeUnmount(stopOtpTimer)
</script>

<template>
  <main class="min-h-screen bg-slate-950 px-4 py-10 text-slate-100 selection:bg-blue-600 selection:text-white">
    <section class="mx-auto w-full max-w-xl rounded-2xl border border-slate-800 bg-slate-900/80 p-6 shadow-2xl backdrop-blur-md sm:p-8">
      <!-- Panel LOGIN -->
      <template v-if="panel === 'login'">
        <header class="mb-6 text-center">
          <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-xl border border-blue-500/20 bg-blue-600/10 text-blue-400">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
          </div>
          <h1 class="text-xl font-bold tracking-tight text-white">Masuk ke Akun</h1>
          <p class="mt-1 text-xs text-slate-400">Gunakan kata sandi atau kode OTP untuk melanjutkan</p>
        </header>

        <div v-if="message" class="mb-4 rounded-xl border p-3 text-center text-xs font-medium" :class="messageType === 'success' ? 'border-emerald-900/50 bg-emerald-950/40 text-emerald-300' : 'border-red-900/50 bg-red-950/40 text-red-400'">{{ message }}</div>

        <div class="mb-5 flex gap-2 rounded-xl border border-slate-800 bg-slate-950/80 p-1">
          <button type="button" class="flex-1 rounded-lg py-2 text-sm font-medium transition" :class="mode === 'password' ? 'bg-blue-600 text-white' : 'text-slate-400 hover:text-slate-200'" @click="switchMode('password')">Login Password</button>
          <button type="button" class="flex-1 rounded-lg py-2 text-sm font-medium transition" :class="mode === 'otp' ? 'bg-blue-600 text-white' : 'text-slate-400 hover:text-slate-200'" @click="switchMode('otp')">Login OTP</button>
        </div>

        <form class="space-y-4" @submit.prevent="mode === 'password' ? loginWithPassword() : (otpSent ? verifyOtp() : sendOtp())">
          <div>
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-400">Alamat Email</label>
            <input v-model="email" type="email" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-4 py-2.5 text-sm text-slate-200 outline-none transition focus:border-blue-600 focus:ring-1 focus:ring-blue-600" placeholder="nama@gmail.com" autocomplete="username" />
          </div>

          <div v-if="mode === 'password'">
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-400">Kata Sandi</label>
            <div class="relative">
              <input v-model="password" :type="showPassword ? 'text' : 'password'" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-4 py-2.5 pr-11 text-sm text-slate-200 outline-none transition focus:border-blue-600 focus:ring-1 focus:ring-blue-600" placeholder="Kata sandi Anda" autocomplete="current-password" />
              <button type="button" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-500 hover:text-slate-200" @click="showPassword = !showPassword">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /><circle cx="12" cy="12" r="3" /></svg>
              </button>
            </div>
            <div class="mt-3 flex items-center justify-between">
              <label class="inline-flex items-center gap-2 text-sm text-slate-300"><input v-model="remember" type="checkbox" class="rounded border-slate-700 bg-slate-950 text-blue-500 focus:ring-blue-500" /> Ingat saya</label>
              <button type="button" class="text-xs font-semibold text-blue-400 hover:underline" @click="panel = 'forgot'; clearMessage()">Lupa kata sandi?</button>
            </div>
          </div>

          <div v-else-if="!otpSent" class="rounded-xl border border-blue-900/40 bg-blue-950/20 p-4 text-center text-xs text-blue-200">Masukkan email, lalu kirim kode OTP.</div>

          <div v-else class="space-y-3">
            <div class="rounded-xl border border-slate-800 bg-slate-950 p-3 text-center text-xs text-slate-400">Kode dikirim ke <span class="font-semibold text-blue-400">{{ email }}</span></div>
            <input v-model="otp" inputmode="numeric" maxlength="6" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-4 py-2.5 text-center text-sm font-bold tracking-[0.4em] text-slate-200 outline-none focus:border-emerald-500" placeholder="••••••" autocomplete="one-time-code" />
            <p class="text-center text-xs font-semibold" :class="otpExpired ? 'text-red-400' : 'text-amber-400'">Kode berlaku selama {{ formattedOtpTime }}</p>
            <button type="button" class="w-full text-xs font-semibold text-blue-400 hover:underline" :disabled="loading" @click="sendOtp">Kirim ulang kode</button>
          </div>

          <button type="submit" class="w-full rounded-xl py-2.5 text-sm font-medium text-white shadow-md transition disabled:cursor-not-allowed disabled:opacity-60" :class="mode === 'otp' ? 'bg-emerald-600 hover:bg-emerald-500 shadow-emerald-900/20' : 'bg-blue-600 hover:bg-blue-500 shadow-blue-900/20'" :disabled="loading || (mode === 'otp' && otpSent && otpExpired)">
            {{ loading ? 'Memproses...' : mode === 'otp' ? (otpSent ? 'Verifikasi & Masuk' : 'Kirim Kode OTP') : 'Masuk' }}
          </button>

          <p class="text-center text-xs text-slate-400">
            Belum punya akun?
            <router-link to="/register" class="font-semibold text-blue-400 hover:underline">Daftar sekarang</router-link>
          </p>
        </form>
      </template>

      <!-- Panel LUPA KATA SANDI -->
      <template v-else>
        <header class="mb-6 text-center">
          <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-xl border border-amber-500/20 bg-amber-600/10 text-amber-400">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" /></svg>
          </div>
          <h1 class="text-xl font-bold tracking-tight text-white">Atur Ulang Kata Sandi</h1>
          <p class="mt-1 text-xs text-slate-400">Masukkan email terdaftar untuk menerima kode verifikasi</p>
        </header>

        <div v-if="message" class="mb-4 rounded-xl border p-3 text-center text-xs font-medium" :class="messageType === 'success' ? 'border-emerald-900/50 bg-emerald-950/40 text-emerald-300' : 'border-red-900/50 bg-red-950/40 text-red-400'">{{ message }}</div>

        <form v-if="forgotStep === 1" class="space-y-4" @submit.prevent="requestResetOtp">
          <div>
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-400">Alamat Email Terdaftar</label>
            <input v-model="forgotEmail" type="email" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-4 py-2.5 text-sm text-slate-200 outline-none transition focus:border-blue-600 focus:ring-1 focus:ring-blue-600" placeholder="nama@gmail.com" autocomplete="username" />
          </div>

          <button type="submit" class="w-full rounded-xl bg-blue-600 py-2.5 text-sm font-medium text-white shadow-md shadow-blue-900/20 transition hover:bg-blue-500 disabled:cursor-not-allowed disabled:opacity-60" :disabled="loading">
            {{ loading ? 'Memproses...' : 'Kirim Kode OTP' }}
          </button>
        </form>

        <!-- Langkah 2: verifikasi kode OTP -->
        <form v-else-if="forgotStep === 2" class="space-y-4" @submit.prevent="verifyResetOtp">
          <div class="rounded-xl border border-slate-800 bg-slate-950 p-3 text-center text-xs text-slate-400">Kode dikirim ke <span class="font-semibold text-blue-400">{{ forgotEmail }}</span> — berlaku {{ formattedOtpTime }}</div>

          <div>
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-400">Kode OTP</label>
            <input v-model="forgotOtp" inputmode="numeric" maxlength="6" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-4 py-2.5 text-center text-sm font-bold tracking-[0.4em] text-slate-200 outline-none focus:border-emerald-500" placeholder="••••••" autocomplete="one-time-code" />
          </div>

          <button type="submit" class="w-full rounded-xl bg-blue-600 py-2.5 text-sm font-medium text-white shadow-md shadow-blue-900/20 transition hover:bg-blue-500 disabled:cursor-not-allowed disabled:opacity-60" :disabled="loading">
            {{ loading ? 'Memverifikasi...' : 'Verifikasi Kode' }}
          </button>

          <button type="button" class="w-full text-xs font-semibold text-blue-400 hover:underline" :disabled="loading" @click="requestResetOtp">Kirim ulang kode</button>
        </form>

        <!-- Langkah 3: kata sandi baru -->
        <form v-else class="space-y-4" @submit.prevent="submitResetPassword">
          <div class="rounded-xl border border-emerald-900/50 bg-emerald-950/40 p-3 text-center text-xs text-emerald-300">✓ Kode terverifikasi — kode dikirim ke <span class="font-semibold text-blue-400">{{ forgotEmail }}</span></div>

          <input v-model="forgotOtp" type="hidden" />

          <div>
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-400">Kata Sandi Baru</label>
            <input v-model="newPassword" type="password" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-4 py-2.5 text-sm text-slate-200 outline-none transition focus:border-blue-600 focus:ring-1 focus:ring-blue-600" placeholder="Min. 12 karakter, huruf + angka, tanpa spasi" autocomplete="new-password" />
          </div>

          <div>
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-400">Konfirmasi Sandi Baru</label>
            <input v-model="newPasswordConfirmation" type="password" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-4 py-2.5 text-sm text-slate-200 outline-none transition focus:border-blue-600 focus:ring-1 focus:ring-blue-600" placeholder="Ulangi sandi baru" autocomplete="new-password" />
          </div>

          <button type="submit" class="w-full rounded-xl bg-blue-600 py-2.5 text-sm font-medium text-white shadow-md shadow-blue-900/20 transition hover:bg-blue-500 disabled:cursor-not-allowed disabled:opacity-60" :disabled="loading">
            {{ loading ? 'Memproses...' : 'Simpan Kata Sandi Baru' }}
          </button>

          <button type="button" class="w-full text-xs font-semibold text-blue-400 hover:underline" :disabled="loading" @click="requestResetOtp">Kirim ulang kode</button>
        </form>

        <p class="mt-5 text-center text-xs text-slate-400">
          <button type="button" class="font-semibold text-blue-400 hover:underline" @click="panel = 'login'; forgotStep = 1; clearMessage()">← Kembali ke login</button>
        </p>
      </template>
    </section>
  </main>
</template>

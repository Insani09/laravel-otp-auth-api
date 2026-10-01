<script setup>
import { computed, onMounted, ref } from 'vue'
import RegionCascading from '../../components/RegionCascading.vue'
import { authHeaders } from '../../auth'
import { fireError } from '../../swal'

const props = defineProps({
  user: { type: Object, default: null },
})

const emit = defineEmits(['close', 'saved'])

const isEdit = computed(() => Boolean(props.user))

const name = ref(props.user?.name || '')
const email = ref(props.user?.email || '')
const role = ref(props.user?.role || 'user')
const password = ref('')
const passwordConfirmation = ref('')
const showPassword = ref(false)

const saving = ref(false)
const errorMessage = ref('')

const regionRef = ref(null)

function apiError(error) {
  const response = error?.response?.data
  const errors = response?.errors
  if (errors) {
    return Object.values(errors).flat().filter(Boolean).join(' ')
  }
  return response?.message || 'Terjadi kesalahan. Silakan coba lagi.'
}

onMounted(async () => {
  // Mode edit: pulihkan wilayah tersimpan (await — bukan setTimeout).
  if (isEdit.value && regionRef.value) {
    try {
      await regionRef.value.restore(props.user)
    } catch (e) {
      console.error('Gagal memulihkan wilayah', e)
    }
  }
})

async function submit() {
  errorMessage.value = ''

  if (!name.value.trim() || !email.value.trim()) {
    errorMessage.value = 'Nama dan email wajib diisi.'
    return
  }
  if (!isEdit.value) {
    if (!password.value) {
      errorMessage.value = 'Kata sandi wajib diisi untuk pengguna baru.'
      return
    }
    if (password.value !== passwordConfirmation.value) {
      errorMessage.value = 'Konfirmasi kata sandi tidak cocok.'
      return
    }
  } else if (password.value && password.value !== passwordConfirmation.value) {
    errorMessage.value = 'Konfirmasi kata sandi tidak cocok.'
    return
  }

  saving.value = true
  try {
    const form = new FormData()
    form.append('name', name.value.trim())
    form.append('email', email.value.trim())
    form.append('role', role.value)

    if (regionRef.value) {
      const payload = regionRef.value.payload()
      Object.entries(payload).forEach(([key, value]) => {
        if (value !== null && value !== undefined && value !== '') {
          form.append(key, value)
        }
      })
    }

    // POST _method=PUT agar update memakai route PUT meski lewat FormData.
    if (isEdit.value) {
      form.append('_method', 'PUT')
      if (password.value) {
        form.append('password', password.value)
        form.append('password_confirmation', passwordConfirmation.value)
      }
      await window.axios.post(`/api/admin/users/${props.user.id}`, form, {
        headers: { ...authHeaders(), 'Content-Type': 'multipart/form-data' },
      })
    } else {
      form.append('password', password.value)
      form.append('password_confirmation', passwordConfirmation.value)
      await window.axios.post('/api/admin/users', form, {
        headers: { ...authHeaders(), 'Content-Type': 'multipart/form-data' },
      })
    }

    emit('saved', { message: isEdit.value ? 'Data pengguna berhasil diperbarui.' : 'Pengguna berhasil ditambahkan.' })
  } catch (error) {
    errorMessage.value = apiError(error)
    fireError(isEdit.value ? 'Gagal memperbarui' : 'Gagal menambahkan', apiError(error))
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 p-4 backdrop-blur-sm" @click.self="emit('close')">
    <section class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl border border-slate-800 bg-slate-900 p-6 shadow-2xl">
      <header class="mb-5 flex items-center justify-between">
        <h2 class="text-lg font-bold text-white">{{ isEdit ? 'Edit Pengguna' : 'Tambah Pengguna' }}</h2>
        <button type="button" class="rounded-lg p-1.5 text-slate-500 transition hover:bg-slate-800 hover:text-slate-200" @click="emit('close')">
          <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
        </button>
      </header>

      <div v-if="errorMessage" class="mb-4 rounded-xl border border-red-900/50 bg-red-950/40 p-3 text-center text-xs font-medium text-red-400">{{ errorMessage }}</div>

      <form class="space-y-4" @submit.prevent="submit">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-400">Nama Lengkap</label>
            <input v-model="name" type="text" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-4 py-2.5 text-sm text-slate-200 outline-none transition focus:border-blue-600" />
          </div>
          <div>
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-400">Email</label>
            <input v-model="email" type="email" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-4 py-2.5 text-sm text-slate-200 outline-none transition focus:border-blue-600" />
          </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-400">Role</label>
            <select v-model="role" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-4 py-2.5 text-sm text-slate-200 outline-none transition focus:border-blue-600">
              <option value="user">User</option>
              <option value="admin">Admin</option>
            </select>
          </div>
          <div>
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-400">
              {{ isEdit ? 'Kata Sandi Baru (opsional)' : 'Kata Sandi' }}
            </label>
            <div class="relative">
              <input v-model="password" :type="showPassword ? 'text' : 'password'" :required="!isEdit" autocomplete="new-password" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-4 py-2.5 pr-11 text-sm text-slate-200 outline-none transition focus:border-blue-600" placeholder="Min. 12 karakter, huruf + angka" />
              <button type="button" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-500 hover:text-slate-200" @click="showPassword = !showPassword">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /><circle cx="12" cy="12" r="3" /></svg>
              </button>
            </div>
          </div>
        </div>

        <div v-if="password || !isEdit">
          <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-400">Konfirmasi Kata Sandi</label>
          <input v-model="passwordConfirmation" :type="showPassword ? 'text' : 'password'" autocomplete="new-password" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-4 py-2.5 text-sm text-slate-200 outline-none transition focus:border-blue-600" placeholder="Ulangi kata sandi" />
        </div>

        <hr class="border-slate-800" />

        <!-- Wilayah: dipulihkan otomatis pada mode edit -->
        <RegionCascading ref="regionRef" @change="() => {}" />

        <div class="flex justify-end gap-3 pt-2">
          <button type="button" class="rounded-xl border border-slate-700 px-5 py-2.5 text-sm font-semibold text-slate-300 transition hover:border-slate-500" @click="emit('close')">Batal</button>
          <button type="submit" class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-blue-900/20 transition hover:bg-blue-500 disabled:cursor-not-allowed disabled:opacity-60" :disabled="saving">
            {{ saving ? 'Menyimpan...' : 'Simpan' }}
          </button>
        </div>
      </form>
    </section>
  </div>
</template>

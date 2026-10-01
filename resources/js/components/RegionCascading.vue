<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import SearchSelect from './SearchSelect.vue'

/**
 * Cascading wilayah bersama (Indonesia via IndoRegion, luar negeri via GeoNames
 * proxy) dengan dropdown searchable gaya select2.
 *
 * Perilaku:
 *  - Default: TINGGAL DI INDONESIA → hanya Provinsi → Kota/Kabupaten → Kecamatan.
 *  - Centang "Tinggal di luar negeri" → beralih ke Negara → Provinsi →
 *    Kota (kecamatan tidak berlaku untuk luar negeri).
 *  - Restore tersimpan bersifat await per-level (tanpa setTimeout), jadi nilai
 *    hidden/payload tidak pernah tertimpa sebelum daftar siap.
 *
 * Payload `change` selaras dengan validasi backend:
 * { is_indonesia, negara, negara_kode, provinsi, provinsi_id, kota, kota_id,
 *   kecamatan, kecamatan_id }
 */

const emit = defineEmits(['change'])

const isIndonesia = ref(true)

// Indonesia (IndoRegion)
const provinces = ref([])
const regencies = ref([])
const districts = ref([])
const provinceId = ref('')
const regencyId = ref('')
const districtId = ref('')

// Luar negeri (GeoNames via proxy backend)
const countries = ref([])
const subdivisions = ref([])
const foreignCities = ref([])
const countryCode = ref('')
const subdivisionCode = ref('')
const foreignCityId = ref('')

const loadingProvinces = ref(false)
const loadingRegencies = ref(false)
const loadingDistricts = ref(false)
const loadingCountries = ref(false)
const loadingSubdivisions = ref(false)
const loadingForeignCities = ref(false)

// --- Opsi untuk SearchSelect ---
const provinceOptions = computed(() => provinces.value.map((p) => ({ id: String(p.id), label: p.name })))
const regencyOptions = computed(() => regencies.value.map((r) => ({ id: String(r.id), label: r.name })))
const districtOptions = computed(() => districts.value.map((d) => ({ id: String(d.id), label: d.name })))
const countryOptions = computed(() => countries.value.map((c) => ({ id: String(c.id), label: c.text })))
const subdivisionOptions = computed(() => subdivisions.value.map((s) => ({ id: String(s.id), label: s.text })))
const foreignCityOptions = computed(() => foreignCities.value.map((c) => ({ id: String(c.id), label: c.text })))

const selectedProvince = computed(() =>
  provinces.value.find((p) => String(p.id) === String(provinceId.value)) || null,
)
const selectedRegency = computed(() =>
  regencies.value.find((r) => String(r.id) === String(regencyId.value)) || null,
)
const selectedDistrict = computed(() =>
  districts.value.find((d) => String(d.id) === String(districtId.value)) || null,
)
const selectedCountry = computed(() =>
  countries.value.find((c) => String(c.id) === String(countryCode.value)) || null,
)
const selectedSubdivision = computed(() =>
  subdivisions.value.find((s) => String(s.id) === String(subdivisionCode.value)) || null,
)
const selectedForeignCity = computed(() =>
  foreignCities.value.find((c) => String(c.id) === String(foreignCityId.value)) || null,
)

function asId(value) {
  // ID selalu dikirim sebagai string (validasi backend: string|max:20).
  return value === '' || value === null || value === undefined ? null : String(value)
}

function payload() {
  return {
    is_indonesia: isIndonesia.value,
    negara: isIndonesia.value ? 'Indonesia' : (selectedCountry.value?.label || null),
    negara_kode: isIndonesia.value ? 'ID' : (countryCode.value || null),
    provinsi: isIndonesia.value ? (selectedProvince.value?.label || null) : (selectedSubdivision.value?.label || null),
    provinsi_id: isIndonesia.value ? asId(provinceId.value) : asId(subdivisionCode.value),
    kota: isIndonesia.value ? (selectedRegency.value?.label || null) : (selectedForeignCity.value?.label || null),
    kota_id: isIndonesia.value ? asId(regencyId.value) : asId(foreignCityId.value),
    kecamatan: isIndonesia.value ? (selectedDistrict.value?.label || null) : null,
    kecamatan_id: isIndonesia.value ? asId(districtId.value) : null,
  }
}

function notify() {
  emit('change', payload())
}

// --- Loaders ---
async function loadProvinces() {
  loadingProvinces.value = true
  try {
    const res = await window.axios.get('/api/provinces')
    provinces.value = Array.isArray(res.data) ? res.data : (res.data?.results || [])
  } catch (e) {
    console.error('Gagal memuat provinsi', e)
    provinces.value = []
  } finally {
    loadingProvinces.value = false
  }
}

async function loadRegencies(pid) {
  if (!pid) {
    regencies.value = []
    return
  }
  loadingRegencies.value = true
  try {
    const res = await window.axios.get(`/api/regencies/${pid}`)
    regencies.value = Array.isArray(res.data) ? res.data : (res.data?.results || [])
  } catch (e) {
    console.error('Gagal memuat kota/kabupaten', e)
    regencies.value = []
  } finally {
    loadingRegencies.value = false
  }
}

async function loadDistricts(rid) {
  if (!rid) {
    districts.value = []
    return
  }
  loadingDistricts.value = true
  try {
    const res = await window.axios.get(`/api/districts/${rid}`)
    districts.value = Array.isArray(res.data) ? res.data : (res.data?.results || [])
  } catch (e) {
    console.error('Gagal memuat kecamatan', e)
    districts.value = []
  } finally {
    loadingDistricts.value = false
  }
}

async function loadCountries() {
  if (countries.value.length > 0) {
    return
  }
  loadingCountries.value = true
  try {
    const res = await window.axios.get('/api/geo/countries')
    countries.value = res.data?.results || []
  } catch (e) {
    console.error('Gagal memuat daftar negara', e)
    countries.value = []
  } finally {
    loadingCountries.value = false
  }
}

async function loadSubdivisions(cc) {
  if (!cc) {
    subdivisions.value = []
    return
  }
  loadingSubdivisions.value = true
  try {
    const res = await window.axios.get(`/api/geo/subdivisions/${cc}`)
    subdivisions.value = res.data?.results || []
  } catch (e) {
    console.error('Gagal memuat provinsi', e)
    subdivisions.value = []
  } finally {
    loadingSubdivisions.value = false
  }
}

async function loadForeignCities(cc, admin) {
  if (!cc || !admin) {
    foreignCities.value = []
    return
  }
  loadingForeignCities.value = true
  try {
    const res = await window.axios.get(`/api/geo/cities/${cc}/${admin}`)
    foreignCities.value = res.data?.results || []
  } catch (e) {
    console.error('Gagal memuat kota', e)
    foreignCities.value = []
  } finally {
    loadingForeignCities.value = false
  }
}

// --- Reset helpers ---
function resetIndonesia() {
  provinceId.value = ''
  regencyId.value = ''
  districtId.value = ''
  regencies.value = []
  districts.value = []
}

function resetForeign() {
  countryCode.value = ''
  subdivisionCode.value = ''
  foreignCityId.value = ''
  subdivisions.value = []
  foreignCities.value = []
}

/**
 * Ganti mode Indonesia ↔ luar negeri. Sengaja tidak reset ulang bila mode
 * sudah sesuai (mis. saat restore) supaya pilihan yang baru dipulihkan tidak
 * ikut terhapus.
 */
async function setMode(foreign) {
  isIndonesia.value = !foreign
  if (foreign) {
    if (countries.value.length === 0) {
      await loadCountries()
    }
  } else if (provinces.value.length === 0) {
    await loadProvinces()
  }
  notify()
}

async function onModeChange(event) {
  const foreign = event.target.checked
  if (foreign) {
    resetIndonesia()
  } else {
    resetForeign()
  }
  await setMode(foreign)
}

// --- Perubahan pilihan (Indonesia) ---
async function onProvinceChange() {
  regencyId.value = ''
  districtId.value = ''
  regencies.value = []
  districts.value = []
  await loadRegencies(provinceId.value)
  notify()
}

async function onRegencyChange() {
  districtId.value = ''
  districts.value = []
  await loadDistricts(regencyId.value)
  notify()
}

// --- Perubahan pilihan (luar negeri) ---
async function onCountryChange() {
  subdivisionCode.value = ''
  foreignCityId.value = ''
  subdivisions.value = []
  foreignCities.value = []
  await loadSubdivisions(countryCode.value)
  notify()
}

async function onSubdivisionChange() {
  foreignCityId.value = ''
  foreignCities.value = []
  await loadForeignCities(countryCode.value, subdivisionCode.value)
  notify()
}

function onForeignCityChange() {
  notify()
}

function onDistrictChange() {
  notify()
}

/**
 * Pulihkan pilihan tersimpan secara berurutan (parent → child), menunggu
 * tiap daftar selesai dimuat. `initial` menerima kolom user dari API:
 * negara_kode, provinsi_id, kota_id, kecamatan_id.
 */
async function restore(initial) {
  const foreign = initial?.negara_kode && initial.negara_kode !== 'ID'

  if (foreign) {
    isIndonesia.value = false
    resetIndonesia()
    await loadCountries()
    countryCode.value = initial.negara_kode ? String(initial.negara_kode) : ''
    await loadSubdivisions(countryCode.value)

    subdivisionCode.value = initial.provinsi_id ? String(initial.provinsi_id) : ''
    await loadForeignCities(countryCode.value, subdivisionCode.value)

    foreignCityId.value = initial.kota_id ? String(initial.kota_id) : ''
  } else {
    isIndonesia.value = true
    resetForeign()
    await loadProvinces()

    provinceId.value = initial?.provinsi_id ? String(initial.provinsi_id) : ''
    await loadRegencies(provinceId.value)

    regencyId.value = initial?.kota_id ? String(initial.kota_id) : ''
    await loadDistricts(regencyId.value)

    districtId.value = initial?.kecamatan_id ? String(initial.kecamatan_id) : ''
  }

  notify()
}

async function reset() {
  await restore(null)
}

onMounted(async () => {
  // Muat daftar awal + umumkan payload default saat pertama dipasang
  // (dipakai halaman/modal yang tidak memanggil restore()).
  if (isIndonesia.value && provinces.value.length === 0) {
    await loadProvinces()
  }
  notify()
})

defineExpose({ restore, reset, payload })
</script>

<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <label class="text-xs font-semibold uppercase tracking-wider text-slate-400">Lokasi Domisili</label>
      <label class="inline-flex cursor-pointer items-center gap-2 text-xs text-slate-300">
        <input
          type="checkbox"
          class="rounded border-slate-700 bg-slate-950 text-emerald-500 focus:ring-emerald-500"
          :checked="!isIndonesia"
          @change="onModeChange"
        />
        Tinggal di luar negeri
      </label>
    </div>

    <!-- LUAR NEGERI: Negara → Provinsi → Kota -->
    <div v-if="!isIndonesia" class="space-y-3">
      <div>
        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-400">Negara</label>
        <SearchSelect
          v-model="countryCode"
          :options="countryOptions"
          placeholder="Pilih Negara"
          :loading="loadingCountries"
          @update:modelValue="onCountryChange"
        />
      </div>

      <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <div>
          <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-400">Provinsi</label>
          <SearchSelect
            v-model="subdivisionCode"
            :options="subdivisionOptions"
            placeholder="Pilih Provinsi"
            :disabled="!countryCode"
            :loading="loadingSubdivisions"
            @update:modelValue="onSubdivisionChange"
          />
        </div>
        <div>
          <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-400">Kota</label>
        <SearchSelect
          v-model="foreignCityId"
          :options="foreignCityOptions"
          placeholder="Pilih Kota"
          :disabled="!subdivisionCode"
          :loading="loadingForeignCities"
          @update:modelValue="onForeignCityChange"
        />
        </div>
      </div>
    </div>

    <!-- INDONESIA: Provinsi → Kota/Kabupaten → Kecamatan -->
    <div v-else class="grid grid-cols-1 gap-3 sm:grid-cols-3">
      <div>
        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-400">Provinsi</label>
        <SearchSelect
          v-model="provinceId"
          :options="provinceOptions"
          placeholder="Pilih Provinsi"
          :loading="loadingProvinces"
          @update:modelValue="onProvinceChange"
        />
      </div>

      <div>
        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-400">Kota / Kabupaten</label>
        <SearchSelect
          v-model="regencyId"
          :options="regencyOptions"
          placeholder="Pilih Kota"
          :disabled="!provinceId"
          :loading="loadingRegencies"
          @update:modelValue="onRegencyChange"
        />
      </div>

      <div>
        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-400">Kecamatan</label>
        <SearchSelect
          v-model="districtId"
          :options="districtOptions"
          placeholder="Pilih Kecamatan"
          :disabled="!regencyId"
          :loading="loadingDistricts"
          @update:modelValue="onDistrictChange"
        />
      </div>
    </div>
  </div>
</template>

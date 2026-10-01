<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch, nextTick } from 'vue'

/**
 * Dropdown gaya select2 (searchable combobox) — murni Vue, tanpa jQuery.
 * Props:
 *  - modelValue : nilai terpilih (id sebagai string)
 *  - options    : [{ id, label }]
 *  - placeholder, disabled, loading
 * Emits: update:modelValue
 */
const props = defineProps({
  modelValue: { type: [String, Number], default: '' },
  options: { type: Array, default: () => [] },
  placeholder: { type: String, default: 'Pilih...' },
  disabled: { type: Boolean, default: false },
  loading: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue'])

const open = ref(false)
const query = ref('')
const highlighted = ref(-1)
const root = ref(null)
const searchInput = ref(null)
const listEl = ref(null)

const filtered = computed(() => {
  const q = query.value.trim().toLowerCase()
  if (!q) {
    return props.options
  }
  return props.options.filter((o) => (o.label || '').toLowerCase().includes(q))
})

const selectedOption = computed(
  () => props.options.find((o) => String(o.id) === String(props.modelValue)) || null,
)

const displayLabel = computed(() => {
  if (selectedOption.value) {
    return selectedOption.value.label
  }
  // Nilai terpilih belum ada di daftar (mis. restore sebelum daftar siap).
  return props.modelValue ? String(props.modelValue) : ''
})

watch(open, (isOpen) => {
  if (isOpen) {
    query.value = ''
    highlighted.value = filtered.value.length > 0 ? 0 : -1
    nextTick(() => searchInput.value?.focus())
  }
})

function toggle() {
  if (!props.disabled) {
    open.value = !open.value
  }
}

function pick(option) {
  emit('update:modelValue', String(option.id))
  open.value = false
}

function clearValue() {
  emit('update:modelValue', '')
}

function moveHighlight(delta) {
  const count = filtered.value.length
  if (count === 0) {
    return
  }
  highlighted.value = (highlighted.value + delta + count) % count
  nextTick(() => {
    const node = listEl.value?.children[highlighted.value]
    node?.scrollIntoView({ block: 'nearest' })
  })
}

function onKeydown(event) {
  if (event.key === 'ArrowDown') {
    event.preventDefault()
    moveHighlight(1)
  } else if (event.key === 'ArrowUp') {
    event.preventDefault()
    moveHighlight(-1)
  } else if (event.key === 'Enter') {
    event.preventDefault()
    const option = filtered.value[highlighted.value]
    if (option) {
      pick(option)
    }
  } else if (event.key === 'Escape') {
    event.preventDefault()
    open.value = false
  }
}

function onClickOutside(event) {
  if (root.value && !root.value.contains(event.target)) {
    open.value = false
  }
}

onMounted(() => document.addEventListener('mousedown', onClickOutside))
onBeforeUnmount(() => document.removeEventListener('mousedown', onClickOutside))
</script>

<template>
  <div ref="root" class="relative">
    <!-- Trigger -->
    <button
      type="button"
      class="flex w-full items-center justify-between gap-2 rounded-xl border px-4 py-2.5 text-left text-sm transition"
      :class="[
        disabled ? 'cursor-not-allowed border-slate-800 bg-slate-950 text-slate-500 opacity-60' : 'border-slate-800 bg-slate-950 text-slate-200 hover:border-slate-600',
        open ? 'border-blue-600 ring-1 ring-blue-600' : '',
      ]"
      :disabled="disabled"
      @click="toggle"
    >
      <span class="truncate" :class="displayLabel ? 'uppercase' : 'text-slate-500'">
        {{ loading ? 'Memuat...' : (displayLabel || placeholder) }}
      </span>
      <span class="flex items-center gap-1">
        <button
          v-if="displayLabel && !disabled"
          type="button"
          class="rounded p-0.5 text-slate-500 transition hover:bg-slate-800 hover:text-slate-200"
          aria-label="Hapus pilihan"
          @click.stop="clearValue"
        >
          <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
        </button>
        <svg class="h-4 w-4 shrink-0 text-slate-500 transition" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
      </span>
    </button>

    <!-- Panel -->
    <div
      v-if="open"
      class="absolute z-40 mt-1 w-full overflow-hidden rounded-xl border border-slate-700 bg-slate-900 shadow-2xl"
    >
      <div class="border-b border-slate-800 p-2">
        <input
          ref="searchInput"
          v-model="query"
          type="text"
          class="w-full rounded-lg border border-slate-800 bg-slate-950 px-3 py-2 text-sm text-slate-200 outline-none transition focus:border-blue-600"
          placeholder="Ketik untuk mencari..."
          @keydown="onKeydown"
        />
      </div>

      <div ref="listEl" class="max-h-60 overflow-y-auto">
        <div v-if="loading" class="px-4 py-3 text-center text-xs text-slate-500">Memuat data...</div>

        <div v-else-if="filtered.length === 0" class="px-4 py-3 text-center text-xs text-slate-500">
          {{ query ? 'Tidak ada hasil untuk "' + query + '"' : 'Tidak ada data.' }}
        </div>

        <button
          v-else
          v-for="(option, index) in filtered"
          :key="option.id"
          type="button"
          class="block w-full px-4 py-2 text-left text-sm uppercase transition"
          :class="[
            String(option.id) === String(modelValue) ? 'bg-blue-600/20 font-semibold text-blue-200' : 'text-slate-200',
            index === highlighted ? 'bg-slate-800' : '',
          ]"
          @mousedown.prevent="pick(option)"
          @mouseenter="highlighted = index"
        >
          {{ option.label }}
        </button>
      </div>
    </div>
  </div>
</template>

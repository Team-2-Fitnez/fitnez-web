<script setup lang="ts">
import { ref, watch } from 'vue'
import { excelApi, type ImportResult } from '../../api/excelApi'
import { useSse } from '../../composables/useSse'

const emit = defineEmits<{ done: [] }>()

const file = ref<File | null>(null)
const importType = ref('schedules')
const uploading = ref(false)
const result = ref<ImportResult | null>(null)

const { progress, status, message, connect, disconnect } = useSse()

watch(status, (val) => {
  if (val === 'completed' || val === 'failed') {
    uploading.value = false
  }
})

function resetModal() {
  file.value = null
  importType.value = 'schedules'
  result.value = null
}

function onFileChange(e: Event) {
  const target = e.target as HTMLInputElement
  file.value = target.files?.[0] || null
}

async function doUpload() {
  if (!file.value) return
  uploading.value = true
  result.value = null
  try {
    const resp = await excelApi.upload(file.value, importType.value)
    result.value = resp.data
    if (resp.data.job_id) {
      connect(resp.data.job_id)
    }
  } catch (err: any) {
    result.value = { success: false, message: err.message || 'Upload gagal', job_id: '' }
    uploading.value = false
  }
}

function close() {
  disconnect()
  resetModal()
  emit('done')
}
</script>

<template>
  <div class="modal-overlay" @click.self="close">
    <div class="card modal-panel">
      <h3 class="title-md mb-4">Import Data dari Excel</h3>

      <div v-if="status === 'processing'" class="mb-4">
        <div class="progress-bar-track">
          <div class="progress-bar-fill" :style="{ width: progress + '%' }"></div>
        </div>
        <p class="text-xs text-muted mt-1">{{ message }}</p>
      </div>

      <div v-if="result && status !== 'processing'" class="mb-4">
        <div :class="['alert', result.success ? 'alert-success' : 'alert-error']">
          {{ result.message }}
        </div>
        <div v-if="result.imported !== undefined" class="text-xs text-muted mt-1">
          {{ result.imported }} / {{ result.total }} baris berhasil diimpor
        </div>
        <div v-if="result.errors && result.errors.length" class="mt-2">
          <details>
            <summary class="text-xs text-muted cursor-pointer">Lihat detail error ({{ result.errors.length }})</summary>
            <ul class="mt-1 text-xs text-error" style="max-height: 120px; overflow-y: auto;">
              <li v-for="(err, idx) in result.errors" :key="idx">{{ err }}</li>
            </ul>
          </details>
        </div>
      </div>

      <div class="mb-4">
        <label class="input-label">Tipe Data</label>
        <select v-model="importType" class="select" :disabled="uploading">
          <option value="schedules">Jadwal Latihan</option>
          <option value="members">Data Member</option>
          <option value="workouts">Workout Plan</option>
        </select>
      </div>

      <div class="mb-4">
        <label class="input-label">File Excel/CSV</label>
        <input type="file" accept=".xlsx,.xls,.csv" @change="onFileChange" :disabled="uploading" class="input" />
      </div>

      <div class="flex gap-2 justify-end">
        <button @click="close" class="button button-ghost">Tutup</button>
        <button @click="doUpload" :disabled="!file || uploading" class="button">
          {{ uploading ? 'Mengupload...' : 'Upload & Proses' }}
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.progress-bar-track {
  width: 100%;
  height: 8px;
  background: var(--color-border);
  border-radius: 4px;
  overflow: hidden;
}
.progress-bar-fill {
  height: 100%;
  background: var(--color-primary);
  transition: width 0.3s ease;
  border-radius: 4px;
}
</style>

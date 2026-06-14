<script setup lang="ts">
import { ref, watch } from 'vue'
import { excelApi, type ImportResult } from '@/shared/api/excelApi'
import { useSse } from '@/shared/composables/useSse'

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
    result.value = { success: false, message: err.message || 'Upload failed', job_id: '' }
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
      <h3 class="title-md mb-4">Import Data from Excel</h3>

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
          {{ result.imported }} / {{ result.total }} rows successfully imported
        </div>
        <div v-if="result.errors && result.errors.length" class="mt-2">
          <details>
            <summary class="text-xs text-muted cursor-pointer">View error details ({{ result.errors.length }})</summary>
            <ul class="mt-1 text-xs text-error" style="max-height: 120px; overflow-y: auto;">
              <li v-for="(err, idx) in result.errors" :key="idx">{{ err }}</li>
            </ul>
          </details>
        </div>
      </div>

      <div class="mb-4">
        <label class="input-label">Data Type</label>
        <select v-model="importType" class="select" :disabled="uploading">
          <option value="schedules">Workout Schedule</option>
          <option value="members">Member Data</option>
          <option value="workouts">Workout Plan</option>
        </select>
      </div>

      <div class="mb-4">
        <label class="input-label">Excel/CSV File</label>
        <input type="file" accept=".xlsx,.xls,.csv" @change="onFileChange" :disabled="uploading" class="input" />
      </div>

      <div class="modal-actions flex gap-2 justify-end">
        <button @click="close" class="button button-ghost">Close</button>
        <button @click="doUpload" :disabled="!file || uploading" class="button">
          {{ uploading ? 'Uploading...' : 'Upload & Process' }}
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.modal-overlay {
  align-items: center;
  background: rgba(15, 23, 42, 0.54);
  display: flex;
  inset: 0;
  justify-content: center;
  padding: 1rem;
  position: fixed;
  z-index: 9999;
}

.modal-panel {
  max-height: calc(100dvh - 2rem);
  max-width: 520px;
  overflow-y: auto;
  width: 100%;
}

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

@media (max-width: 640px) {
  .modal-overlay {
    align-items: end;
    padding: 0.75rem;
  }

  .modal-panel {
    border-radius: 1rem !important;
    max-height: calc(100dvh - 1.5rem);
  }

  .modal-actions {
    align-items: stretch;
    flex-direction: column-reverse;
  }

  .modal-actions .button {
    width: 100%;
  }
}
</style>

<script setup lang="ts">
import { ref } from 'vue'
import { http } from '../api/http'

const props = withDefaults(defineProps<{
  importType: 'members' | 'workouts' | 'schedules'
  label?: string
}>(), {
  label: 'Import Excel',
})

const emit = defineEmits<{ imported: [] }>()

const showModal = ref(false)
const file = ref<File | null>(null)
const uploading = ref(false)
const progress = ref(0)
const status = ref<'idle' | 'uploading' | 'processing' | 'done' | 'error'>('idle')
const resultMsg = ref('')
let eventSource: EventSource | null = null

function onFileChange(e: Event) {
  const input = e.target as HTMLInputElement
  file.value = input.files?.[0] || null
}

async function upload() {
  if (!file.value) return

  uploading.value = true
  status.value = 'uploading'
  resultMsg.value = ''

  const formData = new FormData()
  formData.append('file', file.value)
  formData.append('type', props.importType)

  try {
    const res = await http.post('/excel/import', formData) as any

    const jobId = res.job_id
    status.value = 'processing'

    eventSource = new EventSource(`/api/sse/${jobId}`)
    eventSource.addEventListener('progress', (e: MessageEvent) => {
      const data = JSON.parse(e.data)
      progress.value = data.progress
      resultMsg.value = data.message

      if (data.status === 'completed') {
        status.value = 'done'
        eventSource?.close()
        emit('imported')
      } else if (data.status === 'failed') {
        status.value = 'error'
        eventSource?.close()
      }
    })
  } catch {
    status.value = 'error'
    resultMsg.value = 'Failed to upload file.'
  }
}

function close() {
  eventSource?.close()
  showModal.value = false
  file.value = null
  progress.value = 0
  status.value = 'idle'
  resultMsg.value = ''
}
</script>

<template>
  <button class="button button-ghost" style="font-size: 0.85rem;" @click="showModal = true">{{ label }}</button>

  <Teleport to="body">
    <div v-if="showModal" class="modal-overlay" @click.self="close">
      <div class="modal-panel">
        <div class="modal-header">
          <h3>{{ label }}</h3>
          <button class="modal-close" @click="close">&times;</button>
        </div>

        <div class="modal-body" style="display: grid; gap: 1rem;">
          <div v-if="status === 'idle' || status === 'uploading'">
            <label class="file-label" style="display: block; padding: 2rem; border: 2px dashed var(--color-border); border-radius: 12px; text-align: center; cursor: pointer;">
              <input type="file" accept=".xlsx,.xls,.csv" hidden @change="onFileChange" />
              <p style="font-weight: 700;">{{ file ? file.name : 'Click to select Excel/CSV file' }}</p>
              <p class="text-muted" style="font-size: 0.85rem; margin-top: 0.25rem;">.xlsx, .xls, .csv (max 10MB)</p>
            </label>

            <div v-if="status === 'uploading'" style="margin-top: 0.5rem;">
              <div class="progress-bar-track">
                <div class="progress-bar-fill" :style="{ width: progress + '%' }"></div>
              </div>
              <p class="text-muted" style="font-size: 0.85rem; margin-top: 0.25rem;">{{ resultMsg || 'Uploading...' }}</p>
            </div>
          </div>

          <div v-if="status === 'processing'">
            <div class="progress-bar-track">
              <div class="progress-bar-fill" :style="{ width: progress + '%' }"></div>
            </div>
            <p class="text-muted" style="font-size: 0.85rem; margin-top: 0.25rem;">{{ resultMsg }}</p>
          </div>

          <div v-if="status === 'done'" class="panel" style="background: #e6ffed; text-align: center; padding: 1.5rem;">
            <p style="font-weight: 700; color: #22543d;">Import successful!</p>
            <p class="text-muted" style="font-size: 0.85rem; margin-top: 0.25rem;">{{ resultMsg }}</p>
          </div>

          <div v-if="status === 'error'" class="panel" style="background: #fff5f5; text-align: center; padding: 1.5rem;">
            <p style="font-weight: 700; color: #9b2c2c;">Import failed</p>
            <p class="text-muted" style="font-size: 0.85rem; margin-top: 0.25rem;">{{ resultMsg }}</p>
          </div>
        </div>

        <div class="modal-footer">
          <button class="button button-ghost" @click="close">Close</button>
          <button
            v-if="status === 'idle'"
            class="button button-primary"
            :disabled="!file || uploading"
            @click="upload"
          >{{ uploading ? 'Uploading...' : 'Upload & Import' }}</button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.4); display: flex; align-items: center; justify-content: center; z-index: 9999; }
.modal-panel { background: white; border-radius: 16px; width: 90%; max-width: 520px; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,.15); }
.modal-header { display: flex; justify-content: space-between; align-items: center; padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--color-border); }
.modal-header h3 { font-size: 1.1rem; font-weight: 900; }
.modal-close { background: none; border: none; font-size: 1.5rem; cursor: pointer; padding: 0; line-height: 1; }
.modal-body { padding: 1.5rem; }
.modal-footer { display: flex; justify-content: flex-end; gap: 0.75rem; padding: 1rem 1.5rem; border-top: 1px solid var(--color-border); }
.progress-bar-track { height: 8px; background: #e2e8f0; border-radius: 4px; overflow: hidden; }
.progress-bar-fill { height: 100%; background: var(--color-primary); border-radius: 4px; transition: width 0.3s; }
.file-label:hover { border-color: var(--color-primary); background: var(--color-cream); }
</style>

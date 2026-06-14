import { ref, onUnmounted } from 'vue'
import { http } from '@/shared/api/http'

export function useSse() {
  const progress = ref(0)
  const status = ref<'idle' | 'processing' | 'completed' | 'failed'>('idle')
  const message = ref('')
  let eventSource: EventSource | null = null

  function connect(jobId: string) {
    disconnect()
    status.value = 'processing'
    progress.value = 0

    const url = http.url(`/sse/${jobId}`)
    eventSource = new EventSource(url)

    eventSource.addEventListener('progress', (e) => {
      try {
        const data = JSON.parse(e.data)
        progress.value = data.progress
        status.value = data.status
        message.value = data.message || ''
      } catch {
        // ignore parse errors
      }
    })

    eventSource.onerror = () => {
      status.value = 'failed'
      message.value = 'Koneksi terputus'
      disconnect()
    }
  }

  function disconnect() {
    if (eventSource) {
      eventSource.close()
      eventSource = null
    }
  }

  onUnmounted(disconnect)

  return { progress, status, message, connect, disconnect }
}

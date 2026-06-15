import { onMounted, onUnmounted } from 'vue'

export function useAutoRefresh(loadFn: () => any, intervalMs = 8000) {
  let intervalId: ReturnType<typeof setInterval> | null = null
  let running = false

  const tick = async () => {
    if (document.visibilityState !== 'visible' || running) {
      return
    }

    running = true
    try {
      await loadFn()
    } catch (err) {
      console.error('[AutoRefresh] Error executing refresh function:', err)
    } finally {
      running = false
    }
  }

  onMounted(() => {
    intervalId = setInterval(tick, intervalMs)
  })

  onUnmounted(() => {
    if (intervalId) {
      clearInterval(intervalId)
      intervalId = null
    }
  })
}

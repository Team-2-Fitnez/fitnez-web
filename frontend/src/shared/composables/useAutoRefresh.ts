import { onMounted, onUnmounted } from 'vue'

export function useAutoRefresh(loadFn: () => any, intervalMs = 8000) {
  let intervalId: ReturnType<typeof setInterval> | null = null

  const tick = () => {
    if (document.visibilityState === 'visible') {
      try {
        loadFn()
      } catch (err) {
        console.error('[AutoRefresh] Error executing refresh function:', err)
      }
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

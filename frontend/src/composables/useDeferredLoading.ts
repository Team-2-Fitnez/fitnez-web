import { ref, computed } from 'vue'
import { getLoadingProfile } from '../stores/globalRequestLoading'

export function useDeferredLoading() {
  const loading = ref(false)
  const profile = computed(() => getLoadingProfile())

  async function run<T>(task: () => Promise<T>): Promise<T> {
    const startedAt = performance.now()
    loading.value = true
    try {
      return await task()
    } finally {
      const elapsed = performance.now() - startedAt
      const remaining = Math.max(0, profile.value.minVisibleMs - elapsed)
      if (remaining > 0) {
        await new Promise((resolve) => window.setTimeout(resolve, remaining))
      }
      loading.value = false
    }
  }

  const shimmerStyle = computed(() => ({
    '--skeleton-shimmer-ms': `${profile.value.shimmerMs}ms`,
  }))

  return { loading, run, shimmerStyle, profile }
}

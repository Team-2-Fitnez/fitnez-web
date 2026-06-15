import { reactive, readonly } from 'vue'

type NetworkProfile = 'fast' | 'balanced' | 'steady'

type GlobalRequestLoadingState = {
  activeRequests: number
  visible: boolean
  minVisibleMs: number
  shimmerMs: number
  profile: NetworkProfile
  startedAt: number
}

const state = reactive<GlobalRequestLoadingState>({
  activeRequests: 0,
  visible: false,
  minVisibleMs: 900,
  shimmerMs: 1100,
  profile: 'balanced',
  startedAt: 0,
})

let hideTimer: number | null = null

function clearHideTimer() {
  if (hideTimer !== null) {
    window.clearTimeout(hideTimer)
    hideTimer = null
  }
}

export type LoadingProfile = {
  minVisibleMs: number
  shimmerMs: number
  profile: NetworkProfile
}

export function getLoadingProfile(): LoadingProfile {
  const nav = navigator as Navigator & {
    connection?: { effectiveType?: string; saveData?: boolean }
    deviceMemory?: number
  }

  const effectiveType = nav.connection?.effectiveType || '4g'
  const saveData = Boolean(nav.connection?.saveData)
  const deviceMemory = nav.deviceMemory || 4
  const cpuCount = nav.hardwareConcurrency || 4

  let minVisibleMs = 780
  let shimmerMs = 1050
  let profile: NetworkProfile = 'fast'

  if (effectiveType === 'slow-2g' || effectiveType === '2g') {
    minVisibleMs = 1700
    shimmerMs = 1700
    profile = 'steady'
  } else if (effectiveType === '3g') {
    minVisibleMs = 1300
    shimmerMs = 1450
    profile = 'steady'
  } else {
    minVisibleMs = 2400
    shimmerMs = 1120
    profile = 'balanced'
  }

  if (deviceMemory <= 2) {
    minVisibleMs += 220
    shimmerMs += 180
    profile = 'steady'
  } else if (deviceMemory <= 4) {
    minVisibleMs += 100
    shimmerMs += 80
  }

  if (cpuCount <= 4) {
    minVisibleMs += 140
    shimmerMs += 100
    profile = 'steady'
  }

  if (saveData) {
    minVisibleMs += 180
    shimmerMs += 120
    profile = 'steady'
  }

  return {
    minVisibleMs: Math.min(4000, Math.max(2200, minVisibleMs)),
    shimmerMs: Math.min(1900, Math.max(980, shimmerMs)),
    profile,
  }
}

function resolveProfile() {
  return getLoadingProfile()
}

export function beginGlobalRequestLoading() {
  clearHideTimer()

  if (state.activeRequests === 0) {
    const profile = resolveProfile()
    state.minVisibleMs = profile.minVisibleMs
    state.shimmerMs = profile.shimmerMs
    state.profile = profile.profile
    state.startedAt = performance.now()
    state.visible = true
  }

  state.activeRequests += 1
}

export function endGlobalRequestLoading() {
  if (state.activeRequests === 0) return

  state.activeRequests -= 1
  if (state.activeRequests > 0) return

  const elapsed = performance.now() - state.startedAt
  const remaining = Math.max(0, state.minVisibleMs - elapsed)

  clearHideTimer()
  hideTimer = window.setTimeout(() => {
    if (state.activeRequests === 0) {
      state.visible = false
    }
  }, remaining)
}

export function useGlobalRequestLoading() {
  return readonly(state)
}

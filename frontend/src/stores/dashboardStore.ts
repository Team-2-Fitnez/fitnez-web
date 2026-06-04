import { defineStore } from 'pinia'
import { dashboardApi } from '../api/dashboardApi'
import type { DashboardSummary } from '../types/dashboard'

export const useDashboardStore = defineStore('dashboard', {
  state: () => ({
    summary: null as DashboardSummary | null,
    loading: false,
    eventSource: null as EventSource | null,
  }),

  actions: {
    async load() {
      this.loading = true
      try {
        const response = await dashboardApi.summary()
        this.summary = response.data
      } finally {
        this.loading = false
      }
    },

    startLive() {
      this.stopLive()

      const source = new EventSource(dashboardApi.streamUrl())
      this.eventSource = source

      source.addEventListener('dashboard', (event) => {
        this.summary = JSON.parse((event as MessageEvent).data)
      })

      source.onerror = () => {
        this.stopLive()
        setTimeout(() => this.startLive(), 5000)
      }
    },

    stopLive() {
      if (this.eventSource) {
        this.eventSource.close()
        this.eventSource = null
      }
    },
  },
})

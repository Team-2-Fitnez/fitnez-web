import { defineStore } from 'pinia'
import { landingVisitApi } from '../api/landingVisitApi'
import type { LandingVisit } from '../types/landingVisit'

export const useLandingVisitStore = defineStore('landingVisits', {
  state: () => ({
    items: [] as LandingVisit[],
    loading: false,
    search: '',
    browser: '',
    device: '',
    page: 1,
    perPage: 15,
    lastPage: 1,
    total: 0,
    summary: null as null | {
      total_visit_rows: number
      total_page_views: number
      unique_visitors_today: number
      active_visitors_now: number
      by_browser: Array<{ browser_name: string; total: number }>
      by_device: Array<{ device_type: string; total: number }>
      latest: LandingVisit[]
    },
  }),

  actions: {
    async load() {
      this.loading = true

      try {
        const response = await landingVisitApi.list({
          search: this.search,
          browser: this.browser,
          device: this.device,
          page: this.page,
          per_page: this.perPage,
        })

        this.items = response.data.data
        this.page = response.data.current_page
        this.lastPage = response.data.last_page
        this.total = response.data.total
      } finally {
        this.loading = false
      }
    },

    async loadSummary() {
      const response = await landingVisitApi.summary()
      this.summary = response.data as any
    },

    setSearch(value: string) {
      this.search = value
      this.page = 1
      this.load()
    },
  },
})

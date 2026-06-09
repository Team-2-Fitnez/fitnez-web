import { defineStore } from 'pinia'
import { trainerRentHistoryApi } from '../api/trainerRentHistoryApi'
import type { TrainerRentHistory, TrainerRentSummary } from '../types/trainerRentHistory'

export const useTrainerRentHistoryStore = defineStore('trainerRentHistory', {
  state: () => ({
    summary: null as TrainerRentSummary | null,
    items: [] as TrainerRentHistory[],
    loading: false,
    loadingSummary: false,
    search: '',
    status: '',
    startDate: '',
    endDate: '',
    page: 1,
    perPage: 10,
    lastPage: 1,
    total: 0,
  }),

  actions: {
    async loadSummary() {
      this.loadingSummary = true

      try {
        const response = await trainerRentHistoryApi.summary()
        this.summary = response.data
      } finally {
        this.loadingSummary = false
      }
    },

    async load() {
      this.loading = true

      try {
        const response = await trainerRentHistoryApi.list({
          search: this.search,
          status: this.status,
          start_date: this.startDate,
          end_date: this.endDate,
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

    refresh() {
      this.page = 1
      this.loadSummary()
      this.load()
    },

    nextPage() {
      if (this.page >= this.lastPage) return
      this.page += 1
      this.load()
    },

    previousPage() {
      if (this.page <= 1) return
      this.page -= 1
      this.load()
    },
  },
})

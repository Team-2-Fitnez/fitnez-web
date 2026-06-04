import { defineStore } from 'pinia'
import { schedulesApi } from '../api/schedulesApi'
import type { TrainerBooking } from '../types/masterData'

export const useScheduleStore = defineStore('schedules', {
  state: () => ({
    items: [] as TrainerBooking[],
    loading: false,
    search: '',
    status: '',
    page: 1,
    perPage: 10,
    lastPage: 1,
    total: 0,
  }),

  actions: {
    async load() {
      this.loading = true
      try {
        const response = await schedulesApi.list({
          search: this.search,
          status: this.status,
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

    setSearch(value: string) {
      this.search = value
      this.page = 1
      this.load()
    },

    async create(payload: Record<string, unknown>) {
      await schedulesApi.create(payload)
      await this.load()
    },

    async update(id: number, payload: Record<string, unknown>) {
      await schedulesApi.update(id, payload)
      await this.load()
    },

    async remove(id: number) {
      await schedulesApi.remove(id)
      await this.load()
    },
  },
})

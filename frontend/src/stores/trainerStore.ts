import { defineStore } from 'pinia'
import { trainersApi } from '../api/trainersApi'
import type { TrainerDetail } from '../types/masterData'

export const useTrainerStore = defineStore('trainers', {
  state: () => ({
    items: [] as TrainerDetail[],
    loading: false,
    search: '',
    page: 1,
    perPage: 10,
    lastPage: 1,
    total: 0,
  }),

  actions: {
    async load() {
      this.loading = true
      try {
        const response = await trainersApi.list({
          search: this.search,
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
      await trainersApi.create(payload)
      await this.load()
    },

    async update(id: number, payload: Record<string, unknown>) {
      await trainersApi.update(id, payload)
      await this.load()
    },

    async remove(id: number) {
      await trainersApi.remove(id)
      await this.load()
    },
  },
})

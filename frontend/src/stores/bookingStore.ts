import { defineStore } from 'pinia'
import { bookingsApi, type PublicTrainer } from '../api/bookingsApi'
import type { TrainerBooking } from '../types/masterData'

export const useBookingStore = defineStore('bookings', {
  state: () => ({
    trainers: [] as PublicTrainer[],
    bookings: [] as TrainerBooking[],
    loading: false,
    trainersLoading: false,
    page: 1,
    lastPage: 1,
  }),

  actions: {
    async loadTrainers() {
      this.trainersLoading = true
      try {
        const response = await bookingsApi.publicTrainers()
        this.trainers = response.data
      } finally {
        this.trainersLoading = false
      }
    },

    async loadBookings() {
      this.loading = true
      try {
        const response = await bookingsApi.list(this.page)
        this.bookings = response.data.data
        this.page = response.data.current_page
        this.lastPage = response.data.last_page
      } finally {
        this.loading = false
      }
    },

    async createBooking(payload: Record<string, unknown>) {
      await bookingsApi.create(payload)
      await this.loadBookings()
    },

    async updateStatus(id: number, status: string) {
      await bookingsApi.updateStatus(id, status)
      await this.loadBookings()
    },
  },
})

import { defineStore } from 'pinia'
import { notificationsApi } from '@/features/Notifications/api/notificationsApi'
import type { NotificationItem } from '@/features/Dashboard/types/dashboard'

export const useNotificationStore = defineStore('notifications', {
  state: () => ({
    items: [] as NotificationItem[],
    unreadCount: 0,
    loading: false,
    page: 1,
    lastPage: 1,
    perPage: 10,
    total: 0,
    latestId: null as number | null,
  }),

  actions: {
    async load() {
      this.loading = true
      try {
        const response = await notificationsApi.list(this.page, this.perPage)
        this.items = response.data.data
        this.page = response.data.current_page
        this.lastPage = response.data.last_page
        this.total = response.data.total
      } finally {
        this.loading = false
      }
    },

    async loadUnreadCount() {
      const response = await notificationsApi.unreadCount()
      this.unreadCount = response.data.count

      const newLatestId = response.data.latest_id
      if (this.latestId && newLatestId && newLatestId !== this.latestId) {
        window.showFitnezToast?.('🔔 New notification received!', 'info')
        if (this.page !== 1) {
          this.page = 1
        }
        await this.load()
      } else if (!this.latestId && newLatestId) {
        this.latestId = newLatestId
      } else if (newLatestId && newLatestId !== this.latestId) {
        this.latestId = newLatestId
      }
    },

    async markAsRead(id: number) {
      await notificationsApi.markAsRead(id)
      await this.load()
      await this.loadUnreadCount()
    },

    async markAllRead() {
      await notificationsApi.markAllRead()
      this.items = this.items.map((n) => ({ ...n, is_read: true }))
      this.unreadCount = 0
    },

    async goToPage(page: number) {
      if (page < 1 || page > this.lastPage || page === this.page) return
      this.page = page
      await this.load()
    },
  },
})

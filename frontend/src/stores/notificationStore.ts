import { defineStore } from 'pinia'
import { notificationsApi } from '../api/notificationsApi'
import type { NotificationItem } from '../types/dashboard'

export const useNotificationStore = defineStore('notifications', {
  state: () => ({
    items: [] as NotificationItem[],
    unreadCount: 0,
    loading: false,
    page: 1,
    lastPage: 1,
    perPage: 10,
  }),

  actions: {
    async load() {
      this.loading = true
      try {
        const response = await notificationsApi.list(this.page, this.perPage)
        this.items = response.data.data
        this.page = response.data.current_page
        this.lastPage = response.data.last_page
      } finally {
        this.loading = false
      }
    },

    async loadUnreadCount() {
      const response = await notificationsApi.unreadCount()
      this.unreadCount = response.data.count
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
  },
})


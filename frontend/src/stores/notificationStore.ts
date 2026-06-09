import { ref } from 'vue'
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
    total: 0,
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

  async function loadUnreadCount() {
    const response = await notificationsApi.unreadCount()
    unreadCount.value = response.data.count
  }

  async function markAsRead(id: number) {
    await notificationsApi.markAsRead(id)
    await load()
    await loadUnreadCount()
  }

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

import { ref } from 'vue'
import { defineStore } from 'pinia'
import { notificationsApi } from '../api/notificationsApi'
import type { NotificationItem } from '../types/dashboard'

export const useNotificationStore = defineStore('notifications', () => {
  const items = ref<NotificationItem[]>([])
  const unreadCount = ref(0)
  const loading = ref(false)
  const page = ref(1)
  const lastPage = ref(1)
  const perPage = ref(10)

  async function load() {
    loading.value = true
    try {
      const response = await notificationsApi.list(page.value, perPage.value)
      items.value = response.data.data
      page.value = response.data.current_page
      lastPage.value = response.data.last_page
    } finally {
      loading.value = false
    }
  }

  async function loadUnreadCount() {
    const response = await notificationsApi.unreadCount()
    unreadCount.value = response.data.count
  }

  async function markAsRead(id: number) {
    await notificationsApi.markAsRead(id)
    await load()
    await loadUnreadCount()
  }

  async function markAllRead() {
    await notificationsApi.markAllRead()
    items.value = items.value.map((n) => ({ ...n, is_read: true }))
    unreadCount.value = 0
  }

  return { items, unreadCount, loading, page, lastPage, perPage, load, loadUnreadCount, markAsRead, markAllRead }
})


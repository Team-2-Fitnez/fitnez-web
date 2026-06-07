import { http } from './http'
import type { NotificationItem } from '../types/dashboard'
import type { Paginated } from '../types/pagination'

export const notificationsApi = {
  list(page = 1, perPage = 10) {
    return http.get<Paginated<NotificationItem>>(`/notifications?page=${page}&per_page=${perPage}`)
  },
  unreadCount() {
    return http.get<{ count: number }>('/notifications/unread-count')
  },
  markAsRead(id: number) {
    return http.patch<NotificationItem>(`/notifications/${id}/read`)
  },
  markAllRead() {
    return http.patch<null>('/notifications/read-all')
  },
}


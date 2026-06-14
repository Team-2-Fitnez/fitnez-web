import { http } from '@/shared/api/http'
import type { NotificationItem } from '@/features/Dashboard/types/dashboard'
import type { Paginated } from '@/shared/types/pagination'

export const notificationsApi = {
  list(page = 1, perPage = 10) {
    return http.get<Paginated<NotificationItem>>(`/notifications?page=${page}&per_page=${perPage}`)
  },
  unreadCount() {
    return http.get<{ count: number; latest_id: number | null }>('/notifications/unread-count')
  },
  markAsRead(id: number) {
    return http.patch<NotificationItem>(`/notifications/${id}/read`)
  },
  markAllRead() {
    return http.patch<null>('/notifications/read-all')
  },
}


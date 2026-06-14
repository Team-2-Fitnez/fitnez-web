export type DashboardSummary = {
  users_total: number
  new_registrations_today: number
  trainers_total: number
  members_total: number
  schedules_today: number
  transactions_pending: number
  unread_notifications: number
  recent_activity: Array<{
    id: number
    full_name: string
    email: string
    created_at: string
  }>
  updated_at: string
}

export type NotificationItem = {
  id: number
  user_id?: number | null
  title: string
  body: string
  notification_type: string
  is_read: boolean
  created_at: string
}

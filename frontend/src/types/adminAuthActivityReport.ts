import type { FitnezUser } from './auth'
import type { Paginated } from './pagination'

export type SystemLog = {
  id: number
  user_id?: number | null
  action_type: string
  table_affected?: string | null
  record_id?: number | null
  description?: string | null
  created_at: string
  user?: FitnezUser | null
}

export type AuthActivitySummary = {
  total_registered_users: number
  registered_today: number
  registered_this_month: number
  login_success_today: number
  login_failed_today: number
  login_success_this_month: number
  login_failed_this_month: number
  latest_registrations: FitnezUser[]
  latest_logs: SystemLog[]
}

export type AuthActivityLogPage = Paginated<SystemLog>
export type RegistrationReportPage = Paginated<FitnezUser>

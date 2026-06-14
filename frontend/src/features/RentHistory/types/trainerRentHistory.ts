import type { FitnezUser } from '@/features/Auth/types/auth'
import type { Paginated } from '@/shared/types/pagination'

export type TrainerRentPayment = {
  id: number
  invoice_number: string
  user_id: number
  booking_id?: number | null
  payment_type: string
  amount: string | number
  payment_method: string
  payment_status?: string | null
  payment_date?: string | null
  external_reference?: string | null
  user?: FitnezUser | null
}

export type TrainerRentBooking = {
  id: number
  member_id: number
  trainer_id: number
  start_date: string
  end_date: string
  sessions_per_week: number
  session_days: string[]
  session_time: string
  member_notes?: string | null
  status?: string | null
  total_member_price: string | number
  total_trainer_price: string | number
  total_sessions: number
  member?: FitnezUser | null
  trainer?: FitnezUser | null
}

export type TrainerRentHistory = {
  id: number
  trainer_id: number
  payment_id: number
  booking_id: number
  commission_rate: string | number
  trainer_amount: string | number
  status?: string | null
  disbursed_at?: string | null
  payment?: TrainerRentPayment | null
  booking?: TrainerRentBooking | null
}

export type TrainerRentSummary = {
  total_bookings: number
  total_earnings: number
  total_records: number
  total_trainer_amount: number
  total_trainer_amount_trend?: number
  pending_amount: number
  disbursed_amount: number
  this_month_amount: number
}

export type TrainerIncomeBreakdown = {
  mentoring_income: number
  session_income: number
  total_income: number
  total_entries: number
  this_month_income: number
}

export type TrainerRentHistoryPage = Paginated<TrainerRentHistory>

import type { FitnezUser } from './auth'
import type { Paginated } from './pagination'

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
  booking_date: string
  start_time: string
  end_time: string
  session_type: string
  location?: string | null
  member_notes?: string | null
  status?: string | null
  total_price: string | number
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
  total_records: number
  total_trainer_amount: number
  total_trainer_amount_trend?: number
  pending_amount: number
  disbursed_amount: number
  this_month_amount: number
}

export type TrainerRentHistoryPage = Paginated<TrainerRentHistory>

import type { FitnezUser } from '@/features/Auth/types/auth'
import type { Paginated } from '@/shared/types/pagination'
import type { TrainerRentBooking } from '@/features/RentHistory/types/trainerRentHistory'

export type MemberReportPayment = {
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
  booking?: TrainerRentBooking | null
}

export type MemberReportAttendance = {
  id: number
  user_id: number
  check_in_time: string
  check_out_time?: string | null
  attendance_type: string
  booking_id?: number | null
  user?: FitnezUser | null
  booking?: TrainerRentBooking | null
}

export type MemberPaymentAttendanceSummary = {
  total_payments: number
  total_payments_trend?: number
  total_payment_amount: number
  total_payment_amount_trend?: number
  paid_payments: number
  pending_payments: number
  total_attendance: number
  attendance_today: number
  attendance_today_trend?: number
  attendance_this_month: number
}

export type MemberReportPaymentPage = Paginated<MemberReportPayment>
export type MemberReportAttendancePage = Paginated<MemberReportAttendance>

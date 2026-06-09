import { http } from './http'
import type { TrainerBooking } from '../types/masterData'
import type { Paginated } from '../types/pagination'

export type PublicTrainer = {
  id: number
  name: string
  img: string | null
  spec: string
  bio: string
  exp: number
  member_price: number
  base_price: number
  rating: number
}

export type MonthlyBookingPayload = {
  trainer_id: number
  start_date: string
  sessions_per_week: 3 | 5 | 7
  session_days: string[]
  session_time: string
  member_notes?: string
}

export type SessionDatesResponse = {
  dates: string[]
  session_time: string
}

export const bookingsApi = {
  publicTrainers() {
    return http.get<PublicTrainer[]>('/trainers/list')
  },

  list(page = 1, perPage = 20) {
    return http.get<Paginated<TrainerBooking>>(`/bookings?page=${page}&per_page=${perPage}`)
  },

  create(payload: MonthlyBookingPayload) {
    return http.post<TrainerBooking>('/bookings', payload)
  },

  uploadProof(id: number, file: File) {
    const form = new FormData()
    form.append('payment_proof', file)
    return http.post<TrainerBooking>(`/bookings/${id}/upload-proof`, form)
  },

  updateStatus(id: number, status: string) {
    return http.patch<TrainerBooking>(`/bookings/${id}/status`, { status })
  },

  pendingPayments(page = 1) {
    return http.get<Paginated<TrainerBooking>>(`/admin/bookings/pending-payments?page=${page}`)
  },

  confirmPayment(id: number) {
    return http.post<TrainerBooking>(`/admin/bookings/${id}/confirm-payment`)
  },

  rejectPayment(id: number, reason: string) {
    return http.post<TrainerBooking>(`/admin/bookings/${id}/reject-payment`, { reason })
  },

  sessionDates(id: number) {
    return http.get<SessionDatesResponse>(`/bookings/${id}/session-dates`)
  },
}

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
  price: number
  rating: number
}

export const bookingsApi = {
  publicTrainers() {
    return http.get<PublicTrainer[]>('/trainers/list')
  },

  list(page = 1, perPage = 20) {
    return http.get<Paginated<TrainerBooking>>(`/bookings?page=${page}&per_page=${perPage}`)
  },

  create(payload: Record<string, unknown>) {
    return http.post<TrainerBooking>('/bookings', payload)
  },

  updateStatus(id: number, status: string) {
    return http.patch<TrainerBooking>(`/bookings/${id}/status`, { status })
  },
}

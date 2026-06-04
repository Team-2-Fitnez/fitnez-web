import type { FitnezUser } from './auth'

export type Role = {
  id: number
  name: string
  description?: string | null
}

export type TrainerDetail = {
  id: number
  user_id: number
  specialization?: string | null
  biography?: string | null
  experience_years?: number | null
  hourly_rate?: number | string | null
  avg_rating?: number | null
  user?: FitnezUser
}

export type TrainerBooking = {
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
  total_price: number | string
  member?: FitnezUser
  trainer?: FitnezUser
}

import type { FitnezUser } from '@/features/Auth/types/auth'

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
  base_price?: number | null
  member_price?: number | null
  user?: FitnezUser
}

export type TrainerBooking = {
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
  base_price_per_session: number
  member_price_per_session: number
  total_member_price: number
  total_trainer_price: number
  total_sessions: number
  payment_proof_path?: string | null
  payment_proof_url?: string | null
  paid_at?: string | null
  member?: FitnezUser
  trainer?: FitnezUser
}

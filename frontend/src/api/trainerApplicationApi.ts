import { http } from './http'
import type { FitnezUser } from '../types/auth'
import type { Paginated } from '../types/pagination'

export type TrainerApplicationStatus = 'not_submitted' | 'pending' | 'approved' | 'rejected' | string

export type TrainerApplication = {
  id: number
  user_id: number
  cv_document_url?: string | null
  certificate_document_url?: string | null
  status: TrainerApplicationStatus
  submitted_at?: string | null
  reviewed_at?: string | null
  reviewed_by_admin_id?: number | null
  admin_notes?: string | null
  user?: FitnezUser
  reviewer?: FitnezUser
}

export type TrainerApplicationStatusResult = {
  application: TrainerApplication | null
  status: TrainerApplicationStatus
  can_access_trainer_workspace: boolean
  has_trainer_profile: boolean
}

function queryString(query: Record<string, unknown>) {
  const params = new URLSearchParams()
  Object.entries(query).forEach(([key, value]) => {
    if (value !== undefined && value !== null && value !== '') params.set(key, String(value))
  })
  const text = params.toString()
  return text ? `?${text}` : ''
}

export const trainerApplicationApi = {
  status() {
    return http.get<TrainerApplicationStatusResult>('/trainer/application')
  },

  apply(payload: Record<string, unknown>) {
    return http.post('/trainer/application', payload)
  },

  submit(cv: File, certificate: File) {
    const form = new FormData()
    form.append('cv', cv)
    form.append('certificate', certificate)
    return http.post<TrainerApplication>('/trainer/application', form)
  },

  enterWorkspace() {
    return http.post<{ redirect_to: string; user: FitnezUser }>('/trainer/workspace/enter')
  },

  leaveWorkspace() {
    return http.post<{ redirect_to: string; user: FitnezUser }>('/trainer/workspace/leave')
  },

  adminList(query: Record<string, unknown>) {
    return http.get<Paginated<TrainerApplication>>(`/admin/trainer-applications${queryString(query)}`)
  },

  approve(id: number, payload: Record<string, unknown> = {}) {
    return http.post<TrainerApplication>(`/admin/trainer-applications/${id}/approve`, payload)
  },

  reject(id: number, admin_notes: string) {
    return http.post<TrainerApplication>(`/admin/trainer-applications/${id}/reject`, { admin_notes })
  },

  documentUrl(id: number, type: 'cv' | 'certificate') {
    return http.url(`/admin/trainer-applications/${id}/documents/${type}`)
  },
}

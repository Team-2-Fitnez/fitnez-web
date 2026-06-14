import { http } from '@/shared/api/http'
import type { FitnezUser } from '@/features/Auth/types/auth'
import type { Paginated } from '@/shared/types/pagination'
import { toQueryString } from '@/shared/utils/queryString'

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

export const trainerApplicationApi = {
  status() {
    return http.get<TrainerApplicationStatusResult>('/trainer/application')
  },

  apply(payload: Record<string, unknown>) {
    return http.post('/trainer/application', payload)
  },

  submit(cv: File, certificate: File, specialization: string, experience_years: number) {
    const form = new FormData()
    form.append('cv', cv)
    form.append('certificate', certificate)
    form.append('specialization', specialization)
    form.append('experience_years', String(experience_years))
    return http.post<TrainerApplication>('/trainer/application', form)
  },

  enterWorkspace() {
    return http.post<{ redirect_to: string; user: FitnezUser }>('/trainer/workspace/enter')
  },

  leaveWorkspace() {
    return http.post<{ redirect_to: string; user: FitnezUser }>('/trainer/workspace/leave')
  },

  adminList(query: Record<string, unknown>) {
    return http.get<Paginated<TrainerApplication>>(`/admin/trainer-applications${toQueryString(query)}`)
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

  async downloadDocument(id: number, type: 'cv' | 'certificate') {
    const baseUrl = import.meta.env.VITE_API_BASE_URL || '/api'
    const response = await fetch(`${baseUrl}/admin/trainer-applications/${id}/documents/${type}/stream`, {
      headers: { 'Authorization': `Bearer ${http.token()}` },
    })
    if (!response.ok) throw new Error('Failed to download document')
    const blob = await response.blob()
    const url = URL.createObjectURL(blob)
    window.open(url, '_blank')
    setTimeout(() => URL.revokeObjectURL(url), 60000)
  },
}

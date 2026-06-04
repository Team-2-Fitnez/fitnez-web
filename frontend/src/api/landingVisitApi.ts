import { http } from './http'
import type { LandingVisit, LandingVisitPayload } from '../types/landingVisit'
import type { Paginated } from '../types/pagination'

function queryString(query: Record<string, unknown>) {
  const params = new URLSearchParams()

  Object.entries(query).forEach(([key, value]) => {
    if (value !== undefined && value !== null && value !== '') {
      params.set(key, String(value))
    }
  })

  const text = params.toString()
  return text ? `?${text}` : ''
}

export const landingVisitApi = {
  track(payload: LandingVisitPayload) {
    return http.post<{
      id: number
      visitor_uuid: string
      session_uuid: string
      visited_at: string
    }>('/analytics/landing-visit', payload)
  },

  list(query: Record<string, unknown>) {
    return http.get<Paginated<LandingVisit>>(`/admin/landing-visits${queryString(query)}`)
  },

  summary() {
    return http.get('/admin/landing-visits/summary')
  },
}

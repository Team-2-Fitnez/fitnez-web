import { http } from '@/shared/api/http'
import type { LandingVisit, LandingVisitPayload } from '@/features/Landing/types/landingVisit'
import type { Paginated } from '@/shared/types/pagination'

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

  heartbeat(payload: LandingVisitPayload) {
    return http.post<{
      id: number
      visitor_uuid: string
      session_uuid: string
      visited_at: string
      last_seen_at: string
    }>('/analytics/landing-visit/heartbeat', payload)
  },

  list(query: Record<string, unknown>) {
    return http.get<Paginated<LandingVisit>>(`/admin/landing-visits${queryString(query)}`)
  },

  summary() {
    return http.get('/admin/landing-visits/summary')
  },
}

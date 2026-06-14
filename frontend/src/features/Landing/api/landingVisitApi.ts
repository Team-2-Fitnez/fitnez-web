import { http } from '@/shared/api/http'
import type { LandingVisit, LandingVisitPayload } from '@/features/Landing/types/landingVisit'
import type { Paginated } from '@/shared/types/pagination'
import { toQueryString } from '@/shared/utils/queryString'

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
    return http.get<Paginated<LandingVisit>>(`/admin/landing-visits${toQueryString(query)}`)
  },

  summary() {
    return http.get('/admin/landing-visits/summary')
  },
}

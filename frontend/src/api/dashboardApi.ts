import { http } from './http'
import type { DashboardSummary } from '../types/dashboard'

export const dashboardApi = {
  summary() {
    return http.get<DashboardSummary>('/dashboard/summary')
  },
  streamUrl() {
    return http.streamUrl('/dashboard/stream')
  },
}

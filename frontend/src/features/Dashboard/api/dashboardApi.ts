import { http } from '@/shared/api/http'
import type { DashboardSummary } from '@/features/Dashboard/types/dashboard'

export const dashboardApi = {
  summary() {
    return http.get<DashboardSummary>('/dashboard/summary')
  },
  streamUrl() {
    return http.streamUrl('/dashboard/stream')
  },
}

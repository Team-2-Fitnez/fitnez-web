import { http } from './http'
import type { DashboardSummary } from '../types/dashboard'

export const dashboardApi = {
  summary() {
    return http.get<DashboardSummary>('/dashboard/summary')
  },
  adminSummary() {
    return http.get<Record<string, any>>('/admin/dashboard')
  },
  trainerSummary() {
    return http.get<Record<string, any>>('/trainer/dashboard')
  },
  streamUrl() {
    return http.streamUrl('/dashboard/stream')
  },
}

import { http } from './http'
import type {
  AuthActivityLogPage,
  AuthActivitySummary,
  RegistrationReportPage,
} from '../types/adminAuthActivityReport'

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

export const adminAuthActivityReportApi = {
  summary() {
    return http.get<AuthActivitySummary>('/admin/auth-activity/summary')
  },

  logs(query: Record<string, unknown>) {
    return http.get<AuthActivityLogPage>(`/admin/auth-activity/logs${queryString(query)}`)
  },

  registrations(query: Record<string, unknown>) {
    return http.get<RegistrationReportPage>(`/admin/auth-activity/registrations${queryString(query)}`)
  },
}

import { http } from '@/shared/api/http'
import type {
  AuthActivityLogPage,
  AuthActivitySummary,
  RegistrationReportPage,
} from '@/features/Reports/types/adminAuthActivityReport'
import { toQueryString } from '@/shared/utils/queryString'

export const adminAuthActivityReportApi = {
  summary() {
    return http.get<AuthActivitySummary>('/admin/auth-activity/summary')
  },

  logs(query: Record<string, unknown>) {
    return http.get<AuthActivityLogPage>(`/admin/auth-activity/logs${toQueryString(query)}`)
  },

  registrations(query: Record<string, unknown>) {
    return http.get<RegistrationReportPage>(`/admin/auth-activity/registrations${toQueryString(query)}`)
  },
}

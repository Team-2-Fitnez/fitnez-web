import { http } from './http'
import type {
  MemberPaymentAttendanceSummary,
  MemberReportAttendancePage,
  MemberReportPaymentPage,
} from '../types/memberPaymentAttendanceReport'

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

export const memberPaymentAttendanceReportApi = {
  summary() {
    return http.get<MemberPaymentAttendanceSummary>('/admin/member-reports/summary')
  },

  payments(query: Record<string, unknown>) {
    return http.get<MemberReportPaymentPage>(`/admin/member-reports/payments${queryString(query)}`)
  },

  attendance(query: Record<string, unknown>) {
    return http.get<MemberReportAttendancePage>(`/admin/member-reports/attendance${queryString(query)}`)
  },
}

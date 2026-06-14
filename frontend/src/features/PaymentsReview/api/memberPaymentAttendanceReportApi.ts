import { http } from '@/shared/api/http'
import type {
  MemberPaymentAttendanceSummary,
  MemberReportAttendancePage,
  MemberReportPaymentPage,
} from '@/features/PaymentsReview/types/memberPaymentAttendanceReport'
import { toQueryString } from '@/shared/utils/queryString'

export const memberPaymentAttendanceReportApi = {
  summary() {
    return http.get<MemberPaymentAttendanceSummary>('/admin/member-reports/summary')
  },

  payments(query: Record<string, unknown>) {
    return http.get<MemberReportPaymentPage>(`/admin/member-reports/payments${toQueryString(query)}`)
  },

  attendance(query: Record<string, unknown>) {
    return http.get<MemberReportAttendancePage>(`/admin/member-reports/attendance${toQueryString(query)}`)
  },
}

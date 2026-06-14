import { http } from '@/shared/api/http'
import type {
  TrainerMemberFitnessDetail,
  TrainerMonitoringMemberPage,
  TrainerMonitoringSummary,
} from '@/features/MemberProgressMonitoring/types/trainerMonitoring'
import { toQueryString } from '@/shared/utils/queryString'

export const trainerMonitoringApi = {
  summary() {
    return http.get<TrainerMonitoringSummary>('/trainer/member-monitoring/summary')
  },

  members(query: Record<string, unknown>) {
    return http.get<TrainerMonitoringMemberPage>(`/trainer/member-monitoring/members${toQueryString(query)}`)
  },

  detail(memberId: number) {
    return http.get<TrainerMemberFitnessDetail>(`/trainer/member-monitoring/members/${memberId}`)
  },
}

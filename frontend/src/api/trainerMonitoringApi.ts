import { http } from './http'
import type {
  TrainerMemberFitnessDetail,
  TrainerMonitoringMemberPage,
  TrainerMonitoringSummary,
} from '../types/trainerMonitoring'

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

export const trainerMonitoringApi = {
  summary() {
    return http.get<TrainerMonitoringSummary>('/trainer/member-monitoring/summary')
  },

  members(query: Record<string, unknown>) {
    return http.get<TrainerMonitoringMemberPage>(`/trainer/member-monitoring/members${queryString(query)}`)
  },

  detail(memberId: number) {
    return http.get<TrainerMemberFitnessDetail>(`/trainer/member-monitoring/members/${memberId}`)
  },
}

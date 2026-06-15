import { http } from '@/shared/api/http'
import type { TrainerRentHistoryPage, TrainerRentSummary, TrainerIncomeBreakdown } from '@/features/RentHistory/types/trainerRentHistory'
import { toQueryString } from '@/shared/utils/queryString'

export const trainerRentHistoryApi = {
  summary() {
    return http.get<TrainerRentSummary>('/trainer/incoming-rent-history/summary')
  },

  list(query: Record<string, unknown>) {
    return http.get<TrainerRentHistoryPage>(`/trainer/incoming-rent-history${toQueryString(query)}`)
  },

  breakdown() {
    return http.get<TrainerIncomeBreakdown>('/trainer/incoming-rent-history/breakdown')
  },
}

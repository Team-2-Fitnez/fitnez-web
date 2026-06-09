import { http } from './http'
import type { TrainerRentHistoryPage, TrainerRentSummary } from '../types/trainerRentHistory'

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

export const trainerRentHistoryApi = {
  summary() {
    return http.get<TrainerRentSummary>('/trainer/incoming-rent-history/summary')
  },

  list(query: Record<string, unknown>) {
    return http.get<TrainerRentHistoryPage>(`/trainer/incoming-rent-history${queryString(query)}`)
  },
}

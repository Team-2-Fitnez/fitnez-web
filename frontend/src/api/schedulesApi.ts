import { http } from './http'
import type { Paginated } from '../types/pagination'
import type { TrainerBooking } from '../types/masterData'
import type { TableQuery } from './adminUsersApi'

function queryString(query: TableQuery) {
  const params = new URLSearchParams()
  Object.entries(query).forEach(([key, value]) => {
    if (value !== undefined && value !== null && value !== '') params.set(key, String(value))
  })
  const text = params.toString()
  return text ? `?${text}` : ''
}

export const schedulesApi = {
  list(query: TableQuery) {
    return http.get<Paginated<TrainerBooking>>(`/admin/schedules${queryString(query)}`)
  },
  create(payload: Record<string, unknown>) {
    return http.post<TrainerBooking>('/admin/schedules', payload)
  },
  update(id: number, payload: Record<string, unknown>) {
    return http.put<TrainerBooking>(`/admin/schedules/${id}`, payload)
  },
  remove(id: number) {
    return http.delete<null>(`/admin/schedules/${id}`)
  },
}

import { http } from './http'
import type { Paginated } from '../types/pagination'
import type { TrainerDetail } from '../types/masterData'
import type { TableQuery } from './adminUsersApi'

function queryString(query: TableQuery) {
  const params = new URLSearchParams()
  Object.entries(query).forEach(([key, value]) => {
    if (value !== undefined && value !== null && value !== '') params.set(key, String(value))
  })
  const text = params.toString()
  return text ? `?${text}` : ''
}

export const trainersApi = {
  list(query: TableQuery) {
    return http.get<Paginated<TrainerDetail>>(`/admin/trainers${queryString(query)}`)
  },
  create(payload: Record<string, unknown>) {
    return http.post<TrainerDetail>('/admin/trainers', payload)
  },
  update(id: number, payload: Record<string, unknown>) {
    return http.put<TrainerDetail>(`/admin/trainers/${id}`, payload)
  },
  remove(id: number) {
    return http.delete<null>(`/admin/trainers/${id}`)
  },
}

import { http } from '@/shared/api/http'
import type { Paginated } from '@/shared/types/pagination'
import type { TrainerBooking } from '@/shared/types/masterData'
import type { TableQuery } from '@/features/Auth/api/adminUsersApi'
import { toQueryString } from '@/shared/utils/queryString'

export const schedulesApi = {
  list(query: TableQuery) {
    return http.get<Paginated<TrainerBooking>>(`/admin/schedules${toQueryString(query)}`)
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

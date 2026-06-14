import { http } from '@/shared/api/http'
import type { Paginated } from '@/shared/types/pagination'
import type { TrainerDetail } from '@/shared/types/masterData'
import type { TableQuery } from '@/features/Auth/api/adminUsersApi'
import { toQueryString } from '@/shared/utils/queryString'

export const trainersApi = {
  list(query: TableQuery) {
    return http.get<Paginated<TrainerDetail>>(`/admin/trainers${toQueryString(query)}`)
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

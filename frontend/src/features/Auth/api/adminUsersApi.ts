import { http } from '@/shared/api/http'
import type { FitnezUser } from '@/features/Auth/types/auth'
import type { Paginated } from '@/shared/types/pagination'
import type { Role } from '@/shared/types/masterData'
import { toQueryString } from '@/shared/utils/queryString'

export type TableQuery = {
  search?: string
  role?: string
  status?: string
  page?: number
  per_page?: number
}

export type AdminUserSummary = {
  total_members: number
  total_members_trend: number
  new_members_this_month: number
  new_members_this_month_trend: number
  inactive_members: number
  inactive_members_trend: number
}

export const adminUsersApi = {
  list(query: TableQuery) {
    return http.get<Paginated<FitnezUser>>(`/admin/users${toQueryString(query)}`)
  },
  roles() {
    return http.get<Role[]>('/admin/roles')
  },
  summary() {
    return http.get<AdminUserSummary>('/admin/users/summary')
  },
  create(payload: Record<string, unknown>) {
    return http.post<FitnezUser>('/admin/users', payload)
  },
  update(id: number, payload: Record<string, unknown>) {
    return http.put<FitnezUser>(`/admin/users/${id}`, payload)
  },
  remove(id: number) {
    return http.delete<null>(`/admin/users/${id}`)
  },
}

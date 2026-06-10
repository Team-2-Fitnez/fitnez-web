import { http } from './http'

export type MembershipPackage = {
  id: number
  code: string
  name: string
  duration_months: number
  price: number
  free_class_access: boolean
  benefits?: string[] | null
  is_active?: boolean
}

export type MembershipStatusPayload = {
  status: string
  is_expired: boolean
  is_expiring_soon: boolean
  days_left: number | null
  renewal_deadline_at?: string | null
  membership_package?: MembershipPackage | null
  membership_started_at?: string | null
  membership_expires_at?: string | null
  queued_membership_package?: MembershipPackage | null
  queued_membership_starts_at?: string | null
  queued_membership_expires_at?: string | null
  pending_renewal_payment?: {
    id: number
    amount: number
    package_name: string
  } | null
}

export type RenewalResult = {
  payment: Record<string, unknown>
  membership: MembershipStatusPayload
}

export const memberMembershipApi = {
  status() {
    return http.get<MembershipStatusPayload>('/member/membership/status')
  },

  packages() {
    return http.get<MembershipPackage[]>('/membership-packages?limit=50')
  },

  renew(membership_package_id: number) {
    return http.post<any>('/member/membership/renew', { membership_package_id })
  },

  uploadProof(paymentId: number, file: File) {
    const form = new FormData()
    form.append('payment_proof', file)
    return http.post<any>(`/member/membership/renew/${paymentId}/upload-proof`, form)
  },

  pendingRenewals(page = 1) {
    return http.get<any>(`/admin/payments/pending-renewals?page=${page}`)
  },

  confirmRenewal(paymentId: number) {
    return http.post<any>(`/admin/payments/renewals/${paymentId}/confirm`)
  },

  rejectRenewal(paymentId: number, reason: string) {
    return http.post<any>(`/admin/payments/renewals/${paymentId}/reject`, { reason })
  },

  deleteAccount() {
    return http.delete<null>('/member/account')
  },
}

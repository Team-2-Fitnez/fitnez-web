import { http } from '@/shared/api/http'
import type { ManualPaymentMethod, MembershipPackage, ProspectiveRegistration, StartManualRegistrationPayload } from '@/features/Registration/types/membership'
import type { Paginated } from '@/shared/types/pagination'
import { toQueryString } from '@/shared/utils/queryString'

export const manualRegistrationApi={
  packages(){return http.get<MembershipPackage[]>('/membership-packages?limit=20')},
  paymentMethods(){return http.get<ManualPaymentMethod[]>('/manual-payment-methods?limit=20')},
  start(payload:StartManualRegistrationPayload){return http.post<ProspectiveRegistration>('/auth/prospective-registration/start',payload)},
  uploadProof(payload:{registration_code:string;email:string;payment_proof:File}){const f=new FormData(); f.append('registration_code',payload.registration_code); f.append('email',payload.email); f.append('payment_proof',payload.payment_proof); return http.post<ProspectiveRegistration>('/auth/prospective-registration/upload-proof',f)},
  status(registrationCode:string,email:string){return http.get<ProspectiveRegistration>(`/auth/prospective-registration/status${toQueryString({registration_code:registrationCode,email})}`)},
  adminList(query:Record<string,unknown>){return http.get<Paginated<ProspectiveRegistration>>(`/admin/prospective-members${toQueryString(query)}`)},
  approve(id:number){return http.post(`/admin/prospective-members/${id}/approve`)},
  reject(id:number,reason:string){return http.post(`/admin/prospective-members/${id}/reject`,{reason})},
}

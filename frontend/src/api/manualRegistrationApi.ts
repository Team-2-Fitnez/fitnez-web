import { http } from './http'
import type { ManualPaymentMethod, MembershipPackage, ProspectiveRegistration, StartManualRegistrationPayload } from '../types/membership'
import type { Paginated } from '../types/pagination'
function qs(query: Record<string, unknown>) { const p=new URLSearchParams(); Object.entries(query).forEach(([k,v])=>{ if(v!==undefined&&v!==null&&v!=='') p.set(k,String(v)) }); const s=p.toString(); return s?`?${s}`:'' }
export const manualRegistrationApi={
  packages(){return http.get<MembershipPackage[]>('/membership-packages?limit=20')},
  paymentMethods(){return http.get<ManualPaymentMethod[]>('/manual-payment-methods?limit=20')},
  start(payload:StartManualRegistrationPayload){return http.post<ProspectiveRegistration>('/auth/prospective-registration/start',payload)},
  uploadProof(payload:{registration_code:string;email:string;payment_proof:File}){const f=new FormData(); f.append('registration_code',payload.registration_code); f.append('email',payload.email); f.append('payment_proof',payload.payment_proof); return http.post<ProspectiveRegistration>('/auth/prospective-registration/upload-proof',f)},
  status(registrationCode:string,email:string){return http.get<ProspectiveRegistration>(`/auth/prospective-registration/status${qs({registration_code:registrationCode,email})}`)},
  adminList(query:Record<string,unknown>){return http.get<Paginated<ProspectiveRegistration>>(`/admin/prospective-members${qs(query)}`)},
  approve(id:number){return http.post(`/admin/prospective-members/${id}/approve`)},
  reject(id:number,reason:string){return http.post(`/admin/prospective-members/${id}/reject`,{reason})},
}

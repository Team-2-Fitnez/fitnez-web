import { http } from './http'
export type LandingContent = { brand:string; headline:string; subtitle:string; features:string[]; cta:{register:string; member_login:string} }
export const landingApi = { index:()=>http.get<LandingContent>('/landing') }

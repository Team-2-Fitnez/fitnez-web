import type { FitnezUser } from '@/features/Auth/types/auth'
export type OtpSendResult = { email:string; purpose:string }
export type OtpVerifyResult = { user:FitnezUser }

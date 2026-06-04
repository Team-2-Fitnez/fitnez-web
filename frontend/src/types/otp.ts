import type { FitnezUser } from './auth'
export type OtpSendResult = { email:string; purpose:string }
export type OtpVerifyResult = { user:FitnezUser }

import { http } from '@/shared/api/http'
import type { FitnezUser, LoginResult, RegisterProspectiveMemberPayload } from '@/features/Auth/types/auth'

export const authApi = {
  registerProspectiveMember(payload: RegisterProspectiveMemberPayload) {
    return http.post<{ user: FitnezUser; next_step: string }>(
      '/auth/register-prospective-member',
      payload,
    )
  },

  requestLoginOtp(email: string, password: string) {
    return http.post<{ email: string; requires_otp: boolean; purpose: string }>(
      '/auth/request-login-otp',
      {
        email,
        password,
        channel: 'web',
      },
    )
  },

  verifyLoginOtp(email: string, otp: string) {
    return http.post<LoginResult & { purpose: string }>('/auth/verify-login-otp', {
      email,
      otp,
      channel: 'web',
    })
  },

  forgotPassword(email: string) {
    return http.post<{ email: string; purpose: string }>('/auth/password/forgot', { email })
  },

  resetPassword(email: string, otp: string, password: string, passwordConfirmation: string) {
    return http.post<null>('/auth/password/reset', {
      email,
      otp,
      password,
      password_confirmation: passwordConfirmation,
    })
  },

  login(email: string, password: string) {
    return http.post<LoginResult>('/auth/login', {
      email,
      password,
      channel: 'web',
    })
  },

  memberLogin(email: string, password: string) {
    return http.post<LoginResult>('/auth/member-login', {
      email,
      password,
      channel: 'web',
    })
  },

  me() {
    return http.get<FitnezUser>('/auth/me')
  },

  updateProfile(payload: { full_name: string; age?: number | null; phone?: string | null }) {
    return http.patch<FitnezUser>('/auth/profile', payload)
  },

  logout(token?: string | null) {
    return http.request<null>('/auth/logout', {
      method: 'POST',
      body: JSON.stringify({}),
      headers: token ? { Authorization: `Bearer ${token}` } : undefined,
    })
  },
}

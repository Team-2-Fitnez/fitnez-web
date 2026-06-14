import { authApi } from '@/features/Auth/api/authApi'
import type { RegisterProspectiveMemberPayload } from '@/features/Auth/types/auth'

const AUTH_TOKEN_KEY = 'fitnez_access_token'
const AUTH_USER_KEY = 'fitnez_auth_user'

export const authService = {
  async registerProspectiveMember(payload: RegisterProspectiveMemberPayload) {
    return authApi.registerProspectiveMember(payload)
  },

  async requestLoginOtp(email: string, password: string) {
    return authApi.requestLoginOtp(email, password)
  },

  async verifyLoginOtp(email: string, otp: string) {
    const response = await authApi.verifyLoginOtp(email, otp)
    if (response.data.access_token) {
      localStorage.setItem(AUTH_TOKEN_KEY, response.data.access_token)
    }
    if (response.data.user) {
      localStorage.setItem(AUTH_USER_KEY, JSON.stringify(response.data.user))
    }
    return response.data
  },

  async forgotPassword(email: string) {
    return authApi.forgotPassword(email)
  },

  async resetPassword(email: string, otp: string, password: string, passwordConfirmation: string) {
    return authApi.resetPassword(email, otp, password, passwordConfirmation)
  },

  async memberLogin(email: string, password: string) {
    const response = await authApi.memberLogin(email, password)

    if (response.data.access_token) {
      localStorage.setItem(AUTH_TOKEN_KEY, response.data.access_token)
    }

    if (response.data.user) {
      localStorage.setItem(AUTH_USER_KEY, JSON.stringify(response.data.user))
    }

    return response.data
  },

  async logout(token?: string | null) {
    await authApi.logout(token).catch(() => null)
    localStorage.removeItem(AUTH_TOKEN_KEY)
    localStorage.removeItem(AUTH_USER_KEY)
  },
}

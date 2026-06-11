import { defineStore } from 'pinia'
import { authApi } from '../api/authApi'
import { authService } from '../services/authService'
import type { FitnezUser } from '../types/auth'
import { usePushNotifications } from '../composables/usePushNotifications'

const AUTH_USER_KEY = 'fitnez_auth_user'
const AUTH_TOKEN_KEY = 'fitnez_access_token'

function readCachedUser(): FitnezUser | null {
  if (!localStorage.getItem(AUTH_TOKEN_KEY)) return null

  try {
    const raw = localStorage.getItem(AUTH_USER_KEY)
    return raw ? JSON.parse(raw) : null
  } catch {
    localStorage.removeItem(AUTH_USER_KEY)
    return null
  }
}

function cacheUser(user: FitnezUser | null) {
  if (user) {
    localStorage.setItem(AUTH_USER_KEY, JSON.stringify(user))
  } else {
    localStorage.removeItem(AUTH_USER_KEY)
  }
}

function isAuthError(error: unknown): boolean {
  const status = (error as { status?: number })?.status
  return status === 401 || status === 403 || status === 410
}

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: readCachedUser(),
    loading: false,
    initialized: false,
  }),

  getters: {
    isAuthenticated: (state) => Boolean(state.user),
    isMember: (state) => state.user?.role === 'member',
    isAdmin: (state) => state.user?.role === 'admin',
    isTrainer: (state) => state.user?.role === 'trainer' || Boolean(state.user?.can_access_trainer_workspace),
  },

  actions: {
    hydrateFromStorage() {
      if (!this.user) {
        this.user = readCachedUser()
      }
    },

    clearSession() {
      this.user = null
      cacheUser(null)
      localStorage.removeItem(AUTH_TOKEN_KEY)
    },

    async loadMe() {
      this.loading = true

      try {
        const response = await authApi.me()
        this.user = response.data
        cacheUser(response.data)
      } catch (error) {
        if (isAuthError(error)) {
          this.clearSession()
        } else {
          this.hydrateFromStorage()
        }
      } finally {
        this.loading = false
        this.initialized = true
      }
    },

    async memberLogin(email: string, password: string) {
      this.loading = true

      try {
        const result = await authService.memberLogin(email, password)
        this.user = result.user
        cacheUser(result.user)

        await this.registerPushSubscription()
      } finally {
        this.loading = false
      }
    },

    async updateProfile(payload: { full_name: string; age?: number | null; phone?: string | null }) {
      this.loading = true

      try {
        const response = await authApi.updateProfile(payload)
        this.user = response.data
        cacheUser(response.data)
        return response.data
      } finally {
        this.loading = false
      }
    },

    async logout() {
      await this.unregisterPushSubscription()

      await authService.logout()
      this.clearSession()
      this.initialized = true
    },

    async registerPushSubscription() {
      try {
        const push = usePushNotifications()
        await push.init()
        await push.subscribe()
      } catch (e) {
        console.warn('Push registration skipped (non-blocking):', e)
      }
    },

    async unregisterPushSubscription() {
      try {
        const push = usePushNotifications()
        await push.unsubscribe()
      } catch (e) {
        console.warn('Push unsubscription skipped (non-blocking):', e)
      }
    },
  },
})

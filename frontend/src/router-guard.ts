import type { Router } from 'vue-router'
import { useAuthStore } from './stores/authStore'

function workspaceEntry(user: { role?: string; can_access_trainer_workspace?: boolean } | null | undefined): string {
  if (user?.role === 'admin') return '/admin.html'
  if (user?.role === 'trainer' || user?.can_access_trainer_workspace) return '/trainer.html'
  return '/member.html'
}

export function installGuard(router: Router, allowedRoles?: string[]): void {
  router.beforeEach(async (to) => {
    const auth = useAuthStore()

    // Always load user session if token exists and not initialized yet
    if (!auth.initialized) {
      const token = localStorage.getItem('fitnez_access_token')
      if (token) {
        auth.hydrateFromStorage()
        await auth.loadMe()
      } else {
        auth.clearSession()
        auth.initialized = true
      }
    }

    // Auto-redirect authenticated users away from Login and Register pages
    if (to.path === '/login/member' || to.path === '/register') {
      if (auth.isAuthenticated) {
        window.location.href = workspaceEntry(auth.user)
        return false
      }
    }

    if (to.meta.requiresAuth && !auth.isAuthenticated) {
      const redirectTo = `${window.location.pathname}${window.location.search}${window.location.hash}`
      window.location.href = `/?redirect=${encodeURIComponent(redirectTo)}`
      return false
    }

    if (allowedRoles && auth.user?.role) {
      const isTrainerAllowed = allowedRoles.includes('trainer') && (auth.user.role === 'trainer' || auth.user.can_access_trainer_workspace)
      const isMemberAllowed = allowedRoles.includes('member') && (auth.user.role === 'member' || auth.user.role === 'trainer')
      const hasAllowedRole = allowedRoles.includes(auth.user.role) || isTrainerAllowed || isMemberAllowed

      if (!hasAllowedRole) {
        window.location.href = workspaceEntry(auth.user)
        return false
      }
    }

export function installGuard(router: Router, allowedRoles?: string[]): void {
  router.beforeEach(async (to) => {
    if (!to.meta.requiresAuth) return true

    const auth = useAuthStore()

    if (!auth.initialized) {
      await auth.loadMe()
    }

    if (!auth.isAuthenticated) {
      const token = localStorage.getItem('fitnez_access_token')
      if (token && !auth.initialized) {
        await auth.loadMe()
      }
      if (!auth.isAuthenticated) {
        window.location.href = '/login/member'
        return false
      }
    }

    if (allowedRoles && auth.user?.role && !allowedRoles.includes(auth.user.role)) {
      if (auth.user.role === 'admin') {
        window.location.href = '/admin.html'
      } else {
        window.location.href = '/member.html'
      }
      return false
    }

    if (to.meta.role === 'admin' && auth.user?.role !== 'admin') {
      window.location.href = '/member.html'
      return false
    }

    if (to.meta.role === 'member' && auth.user?.role === 'admin') {
      window.location.href = '/admin.html'
      return false
    }

    if (to.meta.requiresTrainerAccess && !auth.user?.can_access_trainer_workspace && auth.user?.role !== 'trainer') {
      window.location.href = '/member.html'
      return false
    }

    return true
  })
}

import type { Router } from 'vue-router'
import { useAuthStore } from './stores/authStore'

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

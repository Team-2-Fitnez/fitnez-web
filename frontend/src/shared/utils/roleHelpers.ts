export type UserRole = 'admin' | 'member' | 'trainer' | string | null | undefined

export function hasRole(role: UserRole, expected: string | string[]) {
  if (!role) return false
  const allowed = Array.isArray(expected) ? expected : [expected]
  return allowed.includes(role)
}

export function isAdmin(role: UserRole) {
  return hasRole(role, 'admin')
}

export function isTrainer(role: UserRole) {
  return hasRole(role, 'trainer')
}

export function isMember(role: UserRole) {
  return hasRole(role, 'member')
}

export function dashboardRouteFor(role: UserRole) {
  if (isAdmin(role)) return '/admin/dashboard'
  if (isTrainer(role)) return '/trainer/dashboard'
  return '/member/dashboard'
}

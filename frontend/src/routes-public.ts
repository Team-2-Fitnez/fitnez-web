import type { RouteRecordRaw } from 'vue-router'

export const publicRoutes: RouteRecordRaw[] = [
  { path: '/privacy-cookie-policy', name: 'privacy-cookie-policy', component: () => import('@/features/Landing/pages/PrivacyCookiePolicy.vue') },
  { path: '/', name: 'landing', component: () => import('@/features/Landing/pages/LandingPage.vue') },
  { path: '/faq', name: 'faq', component: () => import('@/features/Landing/pages/FaqPage.vue') },
  { path: '/register', name: 'register', component: () => import('@/features/Registration/pages/RegisterProspectiveMemberPage.vue') },
  { path: '/registration-status', name: 'registration-status', component: () => import('@/features/Registration/pages/RegistrationStatusPage.vue') },
  { path: '/verify-otp', redirect: '/forgot-password' },
  { path: '/forgot-password', name: 'forgot-password', component: () => import('@/features/Auth/pages/ForgotPasswordPage.vue') },
  { path: '/login/member', name: 'member-login', component: () => import('@/features/Auth/pages/MemberLoginPage.vue') },
]

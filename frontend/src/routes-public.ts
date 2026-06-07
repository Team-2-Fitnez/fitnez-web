import type { RouteRecordRaw } from 'vue-router'

export const publicRoutes: RouteRecordRaw[] = [
  { path: '/privacy-cookie-policy', name: 'privacy-cookie-policy', component: () => import('./pages/PrivacyCookiePolicy.vue') },
  { path: '/', name: 'landing', component: () => import('./pages/LandingPage.vue') },
  { path: '/faq', name: 'faq', component: () => import('./pages/FaqPage.vue') },
  { path: '/register', name: 'register', component: () => import('./pages/RegisterProspectiveMemberPage.vue') },
  { path: '/registration-status', name: 'registration-status', component: () => import('./pages/RegistrationStatusPage.vue') },
  { path: '/verify-otp', redirect: '/forgot-password' },
  { path: '/forgot-password', name: 'forgot-password', component: () => import('./pages/ForgotPasswordPage.vue') },
  { path: '/login/member', name: 'member-login', component: () => import('./pages/MemberLoginPage.vue') },
]

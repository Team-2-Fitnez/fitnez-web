import { createRouter, createWebHistory } from 'vue-router'
import PrivacyCookiePolicy from '../pages/PrivacyCookiePolicy.vue'

import LandingPage from '../pages/LandingPage.vue'
import RegisterProspectiveMemberPage from '../pages/RegisterProspectiveMemberPage.vue'
import RegistrationStatusPage from '../pages/RegistrationStatusPage.vue'
import MemberLoginPage from '../pages/MemberLoginPage.vue'
import ForgotPasswordPage from '../pages/ForgotPasswordPage.vue'

import AdminDashboardPage from '../pages/admin/AdminDashboardPage.vue'
import ProspectiveMemberReviewPage from '../pages/admin/ProspectiveMemberReviewPage.vue'
import LandingVisitReportPage from '../pages/admin/LandingVisitReportPage.vue'
import AuthActivityReportPage from '../pages/admin/AuthActivityReportPage.vue'
import MemberPaymentAttendanceReportPage from '../pages/admin/MemberPaymentAttendanceReportPage.vue'
import TrainerApplicationReviewPage from '../pages/admin/TrainerApplicationReviewPage.vue'
import AdminNutritionMonitoringPage from '../pages/admin/AdminNutritionMonitoringPage.vue'
import AdminNotificationsPage from '../pages/admin/AdminNotificationsPage.vue'

import MemberDashboardPage from '../pages/member/MemberDashboardPage.vue'
import MemberWorkoutPlanPage from '../pages/member/MemberWorkoutPlanPage.vue'
import MemberMealPlanPage from '../pages/member/MemberMealPlanPage.vue'
import MemberHireTrainerPage from '../pages/member/MemberHireTrainerPage.vue'
import MemberSchedulePage from '../pages/member/MemberSchedulePage.vue'
import MemberMembershipPage from '../pages/member/MemberMembershipPage.vue'
import MemberProfilePage from '../pages/member/MemberProfilePage.vue'
import MemberChatPage from '../pages/member/MemberChatPage.vue'
import MemberNotificationsPage from '../pages/member/MemberNotificationsPage.vue'

import TrainerDashboardPage from '../pages/trainer/TrainerDashboardPage.vue'
import TrainerSchedulePage from '../pages/trainer/TrainerSchedulePage.vue'
import TrainerClassesPage from '../pages/trainer/TrainerClassesPage.vue'
import TrainerMembersPage from '../pages/trainer/TrainerMembersPage.vue'
import TrainerRentHistoryPage from '../pages/trainer/TrainerRentHistoryPage.vue'
import TrainerNotificationsPage from '../pages/trainer/TrainerNotificationsPage.vue'
import TrainerProfilePage from '../pages/trainer/TrainerProfilePage.vue'
import TrainerChatPage from '../pages/trainer/TrainerChatPage.vue'

import { useAuthStore } from '../stores/authStore'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/privacy-cookie-policy',  name: 'privacy-cookie-policy',  component: PrivacyCookiePolicy },
    { path: '/', name: 'landing', component: LandingPage },
    { path: '/register', name: 'register', component: RegisterProspectiveMemberPage },
    { path: '/registration-status', name: 'registration-status', component: RegistrationStatusPage },
    { path: '/verify-otp', redirect: '/forgot-password' },
    { path: '/forgot-password', name: 'forgot-password', component: ForgotPasswordPage },
    { path: '/login/member', name: 'member-login', component: MemberLoginPage },

    { path: '/admin/dashboard', name: 'admin-dashboard', component: AdminDashboardPage, meta: { requiresAuth: true, role: 'admin' } },
    { path: '/admin/prospective-members', name: 'admin-prospective-members', component: ProspectiveMemberReviewPage, meta: { requiresAuth: true, role: 'admin' } },
    { path: '/admin/trainer-applications', name: 'admin-trainer-applications', component: TrainerApplicationReviewPage, meta: { requiresAuth: true, role: 'admin' } },
    { path: '/admin/landing-visits', name: 'admin-landing-visits', component: LandingVisitReportPage, meta: { requiresAuth: true, role: 'admin' } },
    { path: '/admin/auth-activity', name: 'admin-auth-activity', component: AuthActivityReportPage, meta: { requiresAuth: true, role: 'admin' } },
    { path: '/admin/member-reports', name: 'admin-member-reports', component: MemberPaymentAttendanceReportPage, meta: { requiresAuth: true, role: 'admin' } },
    { path: '/admin/nutrition-monitoring', name: 'admin-nutrition-monitoring', component: AdminNutritionMonitoringPage, meta: { requiresAuth: true, role: 'admin' } },
    { path: '/admin/notifications', name: 'admin-notifications', component: AdminNotificationsPage, meta: { requiresAuth: true, role: 'admin' } },

    { path: '/member/dashboard', name: 'member-dashboard', component: MemberDashboardPage, meta: { requiresAuth: true, role: 'member' } },
    { path: '/member/workout-plan', name: 'member-workout-plan', component: MemberWorkoutPlanPage, meta: { requiresAuth: true, role: 'member' } },
    { path: '/member/meal-plan', name: 'member-meal-plan', component: MemberMealPlanPage, meta: { requiresAuth: true, role: 'member' } },
    { path: '/member/hire-trainer', name: 'member-hire-trainer', component: MemberHireTrainerPage, meta: { requiresAuth: true, role: 'member' } },
    { path: '/member/schedule', name: 'member-schedule', component: MemberSchedulePage, meta: { requiresAuth: true, role: 'member' } },
    { path: '/member/chat', name: 'member-chat', component: MemberChatPage, meta: { requiresAuth: true, role: 'member' } },
    { path: '/member/memberships', name: 'member-memberships', component: MemberMembershipPage, meta: { requiresAuth: true, role: 'member' } },
    { path: '/member/profile', name: 'member-profile', component: MemberProfilePage, meta: { requiresAuth: true, role: 'member' } },
    { path: '/member/notifications', name: 'member-notifications', component: MemberNotificationsPage, meta: { requiresAuth: true, role: 'member' } },

    { path: '/trainer/daftar', name: 'trainer-apply', component: () => import('../pages/member/MemberTrainerApplyPage.vue'), meta: { requiresAuth: true, role: 'member' } },

    { path: '/trainer/dashboard', name: 'trainer-dashboard', component: TrainerDashboardPage, meta: { requiresAuth: true, requiresTrainerAccess: true } },
    { path: '/trainer/schedule', name: 'trainer-schedule', component: TrainerSchedulePage, meta: { requiresAuth: true, requiresTrainerAccess: true } },
    { path: '/trainer/classes', name: 'trainer-classes', component: TrainerClassesPage, meta: { requiresAuth: true, requiresTrainerAccess: true } },
    { path: '/trainer/members', name: 'trainer-members', component: TrainerMembersPage, meta: { requiresAuth: true, requiresTrainerAccess: true } },
    { path: '/trainer/chat', name: 'trainer-chat', component: TrainerChatPage, meta: { requiresAuth: true, requiresTrainerAccess: true } },
    { path: '/trainer/rent-history', name: 'trainer-rent-history', component: TrainerRentHistoryPage, meta: { requiresAuth: true, requiresTrainerAccess: true } },
    { path: '/trainer/notifications', name: 'trainer-notifications', component: TrainerNotificationsPage, meta: { requiresAuth: true, requiresTrainerAccess: true } },
    { path: '/trainer/profile', name: 'trainer-profile', component: TrainerProfilePage, meta: { requiresAuth: true, requiresTrainerAccess: true } },

    { path: '/:pathMatch(.*)*', redirect: '/' },
  ],
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()

  if (!to.meta.requiresAuth) return true

  if (!auth.initialized) {
    await auth.loadMe()
  }

  if (!auth.isAuthenticated) {
    return '/login/member'
  }

  if (to.meta.role === 'admin' && auth.user?.role !== 'admin') {
    return '/member/dashboard'
  }

  if (to.meta.role === 'member' && auth.user?.role === 'admin') {
    return '/admin/dashboard'
  }

  if (to.meta.requiresTrainerAccess && !auth.user?.can_access_trainer_workspace && auth.user?.role !== 'trainer') {
    return '/member/profile'
  }

  return true
})

export default router

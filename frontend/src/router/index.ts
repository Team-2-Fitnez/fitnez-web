import { createRouter, createWebHistory } from 'vue-router'
import { installGuard } from '../router-guard'
import PrivacyCookiePolicy from '../pages/PrivacyCookiePolicy.vue'
import LandingPage from '../pages/LandingPage.vue'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/privacy-cookie-policy',  name: 'privacy-cookie-policy',  component: PrivacyCookiePolicy },
    { path: '/', name: 'landing', component: LandingPage },
    { path: '/faq', name: 'faq', component: () => import('../pages/FaqPage.vue') },
    { path: '/register', name: 'register', component: () => import('../pages/RegisterProspectiveMemberPage.vue') },
    { path: '/registration-status', name: 'registration-status', component: () => import('../pages/RegistrationStatusPage.vue') },
    { path: '/verify-otp', redirect: '/forgot-password' },
    { path: '/forgot-password', name: 'forgot-password', component: () => import('../pages/ForgotPasswordPage.vue') },
    { path: '/login/member', name: 'member-login', component: () => import('../pages/MemberLoginPage.vue') },
    { path: '/404', name: 'not-found', component: () => import('../pages/NotFoundPage.vue'), meta: { statusCode: 404 } },

    { path: '/admin/dashboard', name: 'admin-dashboard', component: () => import('../pages/admin/AdminDashboardPage.vue'), meta: { requiresAuth: true, role: 'admin' } },
    { path: '/admin/prospective-members', name: 'admin-prospective-members', component: () => import('../pages/admin/ProspectiveMemberReviewPage.vue'), meta: { requiresAuth: true, role: 'admin' } },
    { path: '/admin/trainer-applications', name: 'admin-trainer-applications', component: () => import('../pages/admin/TrainerApplicationReviewPage.vue'), meta: { requiresAuth: true, role: 'admin' } },
    { path: '/admin/landing-visits', name: 'admin-landing-visits', component: () => import('../pages/admin/LandingVisitReportPage.vue'), meta: { requiresAuth: true, role: 'admin' } },
    { path: '/admin/visitor-analytics', name: 'admin-visitor-analytics', component: () => import('../pages/admin/VisitorAnalyticsPage.vue'), meta: { requiresAuth: true, role: 'admin' } },
    { path: '/admin/auth-activity', name: 'admin-auth-activity', component: () => import('../pages/admin/AuthActivityReportPage.vue'), meta: { requiresAuth: true, role: 'admin' } },
    { path: '/admin/member-reports', redirect: '/admin/check-in-logs' },
    { path: '/admin/nutrition-monitoring', name: 'admin-nutrition-monitoring', component: () => import('../pages/admin/AdminNutritionMonitoringPage.vue'), meta: { requiresAuth: true, role: 'admin' } },
    { path: '/admin/notifications', name: 'admin-notifications', component: () => import('../pages/admin/AdminNotificationsPage.vue'), meta: { requiresAuth: true, role: 'admin' } },
    { path: '/admin/users', name: 'admin-users', component: () => import('../pages/admin/AdminUsersPage.vue'), meta: { requiresAuth: true, role: 'admin' } },
    { path: '/admin/trainers', name: 'admin-trainers', component: () => import('../pages/admin/AdminTrainersPage.vue'), meta: { requiresAuth: true, role: 'admin' } },
    { path: '/admin/packages', name: 'admin-packages', component: () => import('../pages/admin/AdminPackagesPage.vue'), meta: { requiresAuth: true, role: 'admin' } },
    { path: '/admin/schedules', name: 'admin-schedules', component: () => import('../pages/admin/AdminSchedulesPage.vue'), meta: { requiresAuth: true, role: 'admin' } },
    { path: '/admin/payments', name: 'admin-payments', component: () => import('../pages/admin/AdminPaymentsPage.vue'), meta: { requiresAuth: true, role: 'admin' } },
    { path: '/admin/check-in-logs', name: 'admin-check-in-logs', component: () => import('../pages/admin/AdminCheckInLogsPage.vue'), meta: { requiresAuth: true, role: 'admin' } },
    { path: '/admin/classes', name: 'admin-classes', component: () => import('../pages/admin/AdminClassesPage.vue'), meta: { requiresAuth: true, role: 'admin' } },
    { path: '/admin/settings', name: 'admin-settings', component: () => import('../pages/admin/AdminSettingsPage.vue'), meta: { requiresAuth: true, role: 'admin' } },

    { path: '/member/dashboard', redirect: '/member/attendance' },
    { path: '/member/workout-plan', name: 'member-workout-plan', component: () => import('../pages/member/MemberWorkoutPlanPage.vue'), meta: { requiresAuth: true, role: 'member' } },
    { path: '/member/meal-plan', name: 'member-meal-plan', component: () => import('../pages/member/MemberMealPlanPage.vue'), meta: { requiresAuth: true, role: 'member' } },
    { path: '/member/hire-trainer', name: 'member-hire-trainer', component: () => import('../pages/member/MemberHireTrainerPage.vue'), meta: { requiresAuth: true, role: 'member' } },
    { path: '/member/schedule', name: 'member-schedule', component: () => import('../pages/member/MemberSchedulePage.vue'), meta: { requiresAuth: true, role: 'member' } },
    { path: '/member/chat', name: 'member-chat', component: () => import('../pages/member/MemberChatPage.vue'), meta: { requiresAuth: true, role: 'member' } },
    { path: '/member/memberships', redirect: '/member/profile' },
    { path: '/member/profile', name: 'member-profile', component: () => import('../pages/member/MemberProfilePage.vue'), meta: { requiresAuth: true, role: 'member' } },
    { path: '/member/notifications', name: 'member-notifications', component: () => import('../pages/member/MemberNotificationsPage.vue'), meta: { requiresAuth: true, role: 'member' } },
    { path: '/member/attendance', name: 'member-attendance', component: () => import('../pages/member/MemberAttendancePage.vue'), meta: { requiresAuth: true, role: 'member' } },
    { path: '/member/payments', name: 'member-payments', component: () => import('../pages/member/MemberPaymentsPage.vue'), meta: { requiresAuth: true, role: 'member' } },
    { path: '/member/classes', name: 'member-classes', component: () => import('../pages/member/MemberClassesPage.vue'), meta: { requiresAuth: true, role: 'member' } },

    { path: '/trainer/daftar', name: 'trainer-apply', component: () => import('../pages/member/MemberTrainerApplyPage.vue'), meta: { requiresAuth: true, role: 'member' } },

    { path: '/trainer/dashboard', name: 'trainer-dashboard', component: () => import('../pages/trainer/TrainerDashboardPage.vue'), meta: { requiresAuth: true, requiresTrainerAccess: true } },
    { path: '/trainer/schedule', name: 'trainer-schedule', component: () => import('../pages/trainer/TrainerSchedulePage.vue'), meta: { requiresAuth: true, requiresTrainerAccess: true } },
    { path: '/trainer/classes', name: 'trainer-classes', component: () => import('../pages/trainer/TrainerClassesPage.vue'), meta: { requiresAuth: true, requiresTrainerAccess: true } },
    { path: '/trainer/members', name: 'trainer-members', component: () => import('../pages/trainer/TrainerMembersPage.vue'), meta: { requiresAuth: true, requiresTrainerAccess: true } },
    { path: '/trainer/chat', name: 'trainer-chat', component: () => import('../pages/trainer/TrainerChatPage.vue'), meta: { requiresAuth: true, requiresTrainerAccess: true } },
    { path: '/trainer/rent-history', name: 'trainer-rent-history', component: () => import('../pages/trainer/TrainerRentHistoryPage.vue'), meta: { requiresAuth: true, requiresTrainerAccess: true } },
    { path: '/trainer/attendance', name: 'trainer-attendance', component: () => import('../pages/trainer/TrainerAttendancePage.vue'), meta: { requiresAuth: true, requiresTrainerAccess: true } },
    { path: '/trainer/notifications', name: 'trainer-notifications', component: () => import('../pages/trainer/TrainerNotificationsPage.vue'), meta: { requiresAuth: true, requiresTrainerAccess: true } },
    { path: '/trainer/profile', name: 'trainer-profile', component: () => import('../pages/trainer/TrainerProfilePage.vue'), meta: { requiresAuth: true, requiresTrainerAccess: true } },

    { path: '/:pathMatch(.*)*', redirect: '/404' },
  ],
  scrollBehavior(to, _from, savedPosition) {
    if (to.hash) {
      return {
        el: to.hash,
        behavior: 'smooth',
      }
    }
    if (savedPosition) {
      return savedPosition
    }
    return { top: 0 }
  },
})

installGuard(router)
export default router

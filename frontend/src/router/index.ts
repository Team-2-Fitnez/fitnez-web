import { createRouter, createWebHistory } from 'vue-router'
import { installGuard } from '@/router-guard'
import PrivacyCookiePolicy from '@/features/Landing/pages/PrivacyCookiePolicy.vue'
import LandingPage from '@/features/Landing/pages/LandingPage.vue'
import { landingVisitService } from '@/features/Landing/services/landingVisitService'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/privacy-cookie-policy',  name: 'privacy-cookie-policy',  component: PrivacyCookiePolicy },
    { path: '/', name: 'landing', component: LandingPage },
    { path: '/faq', name: 'faq', component: () => import('@/features/Landing/pages/FaqPage.vue') },
    { path: '/register', name: 'register', component: () => import('@/features/Registration/pages/RegisterProspectiveMemberPage.vue') },
    { path: '/registration-status', name: 'registration-status', component: () => import('@/features/Registration/pages/RegistrationStatusPage.vue') },
    { path: '/verify-otp', redirect: '/forgot-password' },
    { path: '/forgot-password', name: 'forgot-password', component: () => import('@/features/Auth/pages/ForgotPasswordPage.vue') },
    { path: '/login/member', name: 'member-login', component: () => import('@/features/Auth/pages/MemberLoginPage.vue') },
    { path: '/400', name: 'bad-request', component: () => import('@/features/ErrorPages/pages/ClientErrorPage.vue'), meta: { statusCode: 400 } },
    { path: '/401', name: 'unauthorized', component: () => import('@/features/ErrorPages/pages/ClientErrorPage.vue'), meta: { statusCode: 401 } },
    { path: '/403', name: 'forbidden', component: () => import('@/features/ErrorPages/pages/ClientErrorPage.vue'), meta: { statusCode: 403 } },
    { path: '/408', name: 'request-timeout', component: () => import('@/features/ErrorPages/pages/ClientErrorPage.vue'), meta: { statusCode: 408 } },
    { path: '/404', name: 'not-found', component: () => import('@/features/ErrorPages/pages/ClientErrorPage.vue'), meta: { statusCode: 404 } },

    { path: '/admin/dashboard', name: 'admin-dashboard', component: () => import('@/features/Dashboard/pages/admin/AdminDashboardPage.vue'), meta: { requiresAuth: true, role: 'admin' } },
    { path: '/admin/prospective-members', name: 'admin-prospective-members', component: () => import('@/features/HireTrainer/pages/admin/ProspectiveMemberReviewPage.vue'), meta: { requiresAuth: true, role: 'admin' } },
    { path: '/admin/trainer-applications', name: 'admin-trainer-applications', component: () => import('@/features/HireTrainer/pages/admin/TrainerApplicationReviewPage.vue'), meta: { requiresAuth: true, role: 'admin' } },
    { path: '/admin/landing-visits', name: 'admin-landing-visits', component: () => import('@/features/Reports/pages/admin/LandingVisitReportPage.vue'), meta: { requiresAuth: true, role: 'admin' } },
    { path: '/admin/visitor-analytics', name: 'admin-visitor-analytics', component: () => import('@/features/Reports/pages/admin/VisitorAnalyticsPage.vue'), meta: { requiresAuth: true, role: 'admin' } },
    { path: '/admin/auth-activity', name: 'admin-auth-activity', component: () => import('@/features/Reports/pages/admin/AuthActivityReportPage.vue'), meta: { requiresAuth: true, role: 'admin' } },
    { path: '/admin/member-reports', redirect: '/admin/check-in-logs' },
    { path: '/admin/notifications', name: 'admin-notifications', component: () => import('@/features/Notifications/pages/admin/AdminNotificationsPage.vue'), meta: { requiresAuth: true, role: 'admin' } },
    { path: '/admin/users', name: 'admin-users', component: () => import('@/features/Auth/pages/admin/AdminUsersPage.vue'), meta: { requiresAuth: true, role: 'admin' } },
    { path: '/admin/trainers', name: 'admin-trainers', component: () => import('@/features/HireTrainer/pages/admin/AdminTrainersPage.vue'), meta: { requiresAuth: true, role: 'admin' } },
    { path: '/admin/packages', name: 'admin-packages', component: () => import('@/features/Payments/pages/admin/AdminPackagesPage.vue'), meta: { requiresAuth: true, role: 'admin' } },
    { path: '/admin/schedules', name: 'admin-schedules', component: () => import('@/features/Schedule/pages/admin/AdminSchedulesPage.vue'), meta: { requiresAuth: true, role: 'admin' } },
    { path: '/admin/payments', name: 'admin-payments', component: () => import('@/features/PaymentsReview/pages/admin/AdminPaymentsPage.vue'), meta: { requiresAuth: true, role: 'admin' } },
    { path: '/admin/check-in-logs', name: 'admin-check-in-logs', component: () => import('@/features/Attendance/pages/admin/AdminCheckInLogsPage.vue'), meta: { requiresAuth: true, role: 'admin' } },
    { path: '/admin/classes', name: 'admin-classes', component: () => import('@/features/Schedule/pages/admin/AdminClassesPage.vue'), meta: { requiresAuth: true, role: 'admin' } },
    { path: '/admin/settings', name: 'admin-settings', component: () => import('@/features/Auth/pages/admin/AdminSettingsPage.vue'), meta: { requiresAuth: true, role: 'admin' } },

    { path: '/member/dashboard', redirect: '/member/attendance' },
    { path: '/member/workout-plan', name: 'member-workout-plan', component: () => import('@/features/Workoutplan/pages/member/MemberWorkoutPlanPage.vue'), meta: { requiresAuth: true, role: 'member' } },
    { path: '/member/meal-plan', name: 'member-meal-plan', component: () => import('@/features/Mealplan/pages/member/MemberMealPlanPage.vue'), meta: { requiresAuth: true, role: 'member' } },
    { path: '/member/hire-trainer', name: 'member-hire-trainer', component: () => import('@/features/HireTrainer/pages/member/MemberHireTrainerPage.vue'), meta: { requiresAuth: true, role: 'member' } },
    { path: '/member/schedule', name: 'member-schedule', component: () => import('@/features/Schedule/pages/member/MemberSchedulePage.vue'), meta: { requiresAuth: true, role: 'member' } },
    { path: '/member/chat', name: 'member-chat', component: () => import('@/features/Chat/pages/member/MemberChatPage.vue'), meta: { requiresAuth: true, role: 'member' } },
    { path: '/member/memberships', name: 'member-memberships', component: () => import('@/features/Payments/pages/member/MemberMembershipPage.vue'), meta: { requiresAuth: true, role: 'member' } },
    { path: '/member/profile', name: 'member-profile', component: () => import('@/features/Profile/pages/member/MemberProfilePage.vue'), meta: { requiresAuth: true, role: 'member' } },
    { path: '/member/notifications', name: 'member-notifications', component: () => import('@/features/Notifications/pages/member/MemberNotificationsPage.vue'), meta: { requiresAuth: true, role: 'member' } },
    { path: '/member/attendance', name: 'member-attendance', component: () => import('@/features/Attendance/pages/member/MemberAttendancePage.vue'), meta: { requiresAuth: true, role: 'member' } },
    { path: '/member/payments', name: 'member-payments', component: () => import('@/features/Payments/pages/member/MemberPaymentsPage.vue'), meta: { requiresAuth: true, role: 'member' } },
    { path: '/member/classes', name: 'member-classes', component: () => import('@/features/Schedule/pages/member/MemberClassesPage.vue'), meta: { requiresAuth: true, role: 'member' } },

    { path: '/trainer/daftar', redirect: '/trainer/apply' },
    { path: '/trainer/apply', name: 'trainer-apply', component: () => import('@/features/HireTrainer/pages/member/MemberTrainerApplyPage.vue'), meta: { requiresAuth: true, role: 'member' } },

    { path: '/trainer/dashboard', name: 'trainer-dashboard', component: () => import('@/features/Dashboard/pages/trainer/TrainerDashboardPage.vue'), meta: { requiresAuth: true, requiresTrainerAccess: true } },
    { path: '/trainer/schedule', name: 'trainer-schedule', component: () => import('@/features/Schedule/pages/trainer/TrainerSchedulePage.vue'), meta: { requiresAuth: true, requiresTrainerAccess: true } },
    { path: '/trainer/classes', name: 'trainer-classes', component: () => import('@/features/Schedule/pages/trainer/TrainerClassesPage.vue'), meta: { requiresAuth: true, requiresTrainerAccess: true } },
    { path: '/trainer/members', name: 'trainer-members', component: () => import('@/features/MemberProgressMonitoring/pages/trainer/TrainerMembersPage.vue'), meta: { requiresAuth: true, requiresTrainerAccess: true } },
    { path: '/trainer/chat', name: 'trainer-chat', component: () => import('@/features/Chat/pages/trainer/TrainerChatPage.vue'), meta: { requiresAuth: true, requiresTrainerAccess: true } },
    { path: '/trainer/rent-history', name: 'trainer-rent-history', component: () => import('@/features/RentHistory/pages/trainer/TrainerRentHistoryPage.vue'), meta: { requiresAuth: true, requiresTrainerAccess: true } },
    { path: '/trainer/attendance', name: 'trainer-attendance', component: () => import('@/features/Attendance/pages/trainer/TrainerAttendancePage.vue'), meta: { requiresAuth: true, requiresTrainerAccess: true } },
    { path: '/trainer/notifications', name: 'trainer-notifications', component: () => import('@/features/Notifications/pages/trainer/TrainerNotificationsPage.vue'), meta: { requiresAuth: true, requiresTrainerAccess: true } },
    { path: '/trainer/profile', name: 'trainer-profile', component: () => import('@/features/Profile/pages/trainer/TrainerProfilePage.vue'), meta: { requiresAuth: true, requiresTrainerAccess: true } },

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

router.afterEach((to) => {
  if (
    to.path === '/' ||
    to.path.startsWith('/admin') ||
    to.path.startsWith('/member') ||
    to.path.startsWith('/trainer')
  ) {
    return
  }

  landingVisitService.trackCurrentLandingPage(to.path)
})

export default router

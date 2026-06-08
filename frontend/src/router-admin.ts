import { createRouter, createWebHashHistory } from 'vue-router'
import { installGuard } from './router-guard'

const router = createRouter({
  history: createWebHashHistory('/admin.html'),
  routes: [
    { path: '/', redirect: '/admin/dashboard' },
    { path: '/admin/dashboard', name: 'admin-dashboard', component: () => import('./pages/admin/AdminDashboardPage.vue'), meta: { requiresAuth: true, role: 'admin' } },
    { path: '/admin/prospective-members', name: 'admin-prospective-members', component: () => import('./pages/admin/ProspectiveMemberReviewPage.vue'), meta: { requiresAuth: true, role: 'admin' } },
    { path: '/admin/trainer-applications', name: 'admin-trainer-applications', component: () => import('./pages/admin/TrainerApplicationReviewPage.vue'), meta: { requiresAuth: true, role: 'admin' } },
    { path: '/admin/landing-visits', name: 'admin-landing-visits', component: () => import('./pages/admin/LandingVisitReportPage.vue'), meta: { requiresAuth: true, role: 'admin' } },
    { path: '/admin/visitor-analytics', name: 'admin-visitor-analytics', component: () => import('./pages/admin/VisitorAnalyticsPage.vue'), meta: { requiresAuth: true, role: 'admin' } },
    { path: '/admin/auth-activity', name: 'admin-auth-activity', component: () => import('./pages/admin/AuthActivityReportPage.vue'), meta: { requiresAuth: true, role: 'admin' } },
    { path: '/admin/member-reports', redirect: '/admin/check-in-logs' },
    { path: '/admin/notifications', name: 'admin-notifications', component: () => import('./pages/admin/AdminNotificationsPage.vue'), meta: { requiresAuth: true, role: 'admin' } },
    { path: '/admin/users', name: 'admin-users', component: () => import('./pages/admin/AdminUsersPage.vue'), meta: { requiresAuth: true, role: 'admin' } },
    { path: '/admin/trainers', name: 'admin-trainers', component: () => import('./pages/admin/AdminTrainersPage.vue'), meta: { requiresAuth: true, role: 'admin' } },
    { path: '/admin/packages', name: 'admin-packages', component: () => import('./pages/admin/AdminPackagesPage.vue'), meta: { requiresAuth: true, role: 'admin' } },
    { path: '/admin/schedules', name: 'admin-schedules', component: () => import('./pages/admin/AdminSchedulesPage.vue'), meta: { requiresAuth: true, role: 'admin' } },
    { path: '/admin/payments', name: 'admin-payments', component: () => import('./pages/admin/AdminPaymentsPage.vue'), meta: { requiresAuth: true, role: 'admin' } },
    { path: '/admin/check-in-logs', name: 'admin-check-in-logs', component: () => import('./pages/admin/AdminCheckInLogsPage.vue'), meta: { requiresAuth: true, role: 'admin' } },
    { path: '/admin/classes', name: 'admin-classes', component: () => import('./pages/admin/AdminClassesPage.vue'), meta: { requiresAuth: true, role: 'admin' } },
    { path: '/admin/settings', name: 'admin-settings', component: () => import('./pages/admin/AdminSettingsPage.vue'), meta: { requiresAuth: true, role: 'admin' } },
    { path: '/400', name: 'admin-bad-request', component: () => import('./pages/ClientErrorPage.vue'), meta: { statusCode: 400 } },
    { path: '/401', name: 'admin-unauthorized', component: () => import('./pages/ClientErrorPage.vue'), meta: { statusCode: 401 } },
    { path: '/403', name: 'admin-forbidden', component: () => import('./pages/ClientErrorPage.vue'), meta: { statusCode: 403 } },
    { path: '/408', name: 'admin-request-timeout', component: () => import('./pages/ClientErrorPage.vue'), meta: { statusCode: 408 } },
    { path: '/404', name: 'admin-not-found', component: () => import('./pages/ClientErrorPage.vue'), meta: { statusCode: 404 } },
    { path: '/:pathMatch(.*)*', redirect: '/404' },
  ],
})

installGuard(router, ['admin'])
export default router

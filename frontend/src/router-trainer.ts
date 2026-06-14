import { createRouter, createWebHashHistory } from 'vue-router'
import { installGuard } from '@/router-guard'

const router = createRouter({
  history: createWebHashHistory('/entries/trainer.html'),
  routes: [
    { path: '/', redirect: '/trainer/dashboard' },
    { path: '/trainer/dashboard', name: 'trainer-dashboard', component: () => import('@/features/Dashboard/pages/trainer/TrainerDashboardPage.vue'), meta: { requiresAuth: true, requiresTrainerAccess: true } },
    { path: '/trainer/schedule', name: 'trainer-schedule', component: () => import('@/features/Schedule/pages/trainer/TrainerSchedulePage.vue'), meta: { requiresAuth: true, requiresTrainerAccess: true } },
    { path: '/trainer/classes', name: 'trainer-classes', component: () => import('@/features/Schedule/pages/trainer/TrainerClassesPage.vue'), meta: { requiresAuth: true, requiresTrainerAccess: true } },
    { path: '/trainer/members', name: 'trainer-members', component: () => import('@/features/MemberProgressMonitoring/pages/trainer/TrainerMembersPage.vue'), meta: { requiresAuth: true, requiresTrainerAccess: true } },
    { path: '/trainer/chat', name: 'trainer-chat', component: () => import('@/features/Chat/pages/trainer/TrainerChatPage.vue'), meta: { requiresAuth: true, requiresTrainerAccess: true } },
    { path: '/trainer/rent-history', name: 'trainer-rent-history', component: () => import('@/features/RentHistory/pages/trainer/TrainerRentHistoryPage.vue'), meta: { requiresAuth: true, requiresTrainerAccess: true } },
    { path: '/trainer/notifications', name: 'trainer-notifications', component: () => import('@/features/Notifications/pages/trainer/TrainerNotificationsPage.vue'), meta: { requiresAuth: true, requiresTrainerAccess: true } },
    { path: '/trainer/profile', name: 'trainer-profile', component: () => import('@/features/Profile/pages/trainer/TrainerProfilePage.vue'), meta: { requiresAuth: true, requiresTrainerAccess: true } },
    { path: '/400', name: 'trainer-bad-request', component: () => import('@/features/ErrorPages/pages/ClientErrorPage.vue'), meta: { statusCode: 400 } },
    { path: '/401', name: 'trainer-unauthorized', component: () => import('@/features/ErrorPages/pages/ClientErrorPage.vue'), meta: { statusCode: 401 } },
    { path: '/403', name: 'trainer-forbidden', component: () => import('@/features/ErrorPages/pages/ClientErrorPage.vue'), meta: { statusCode: 403 } },
    { path: '/408', name: 'trainer-request-timeout', component: () => import('@/features/ErrorPages/pages/ClientErrorPage.vue'), meta: { statusCode: 408 } },
    { path: '/404', name: 'trainer-not-found', component: () => import('@/features/ErrorPages/pages/ClientErrorPage.vue'), meta: { statusCode: 404 } },
    { path: '/:pathMatch(.*)*', redirect: '/404' },
  ],
})

installGuard(router, ['trainer'])
export default router

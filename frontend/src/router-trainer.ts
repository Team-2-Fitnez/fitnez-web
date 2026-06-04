import { createRouter, createWebHashHistory } from 'vue-router'
import { installGuard } from './router-guard'

const router = createRouter({
  history: createWebHashHistory('/trainer.html'),
  routes: [
    { path: '/', redirect: '/trainer/dashboard' },
    { path: '/trainer/dashboard', name: 'trainer-dashboard', component: () => import('./pages/trainer/TrainerDashboardPage.vue'), meta: { requiresAuth: true, requiresTrainerAccess: true } },
    { path: '/trainer/schedule', name: 'trainer-schedule', component: () => import('./pages/trainer/TrainerSchedulePage.vue'), meta: { requiresAuth: true, requiresTrainerAccess: true } },
    { path: '/trainer/classes', name: 'trainer-classes', component: () => import('./pages/trainer/TrainerClassesPage.vue'), meta: { requiresAuth: true, requiresTrainerAccess: true } },
    { path: '/trainer/members', name: 'trainer-members', component: () => import('./pages/trainer/TrainerMembersPage.vue'), meta: { requiresAuth: true, requiresTrainerAccess: true } },
    { path: '/trainer/chat', name: 'trainer-chat', component: () => import('./pages/trainer/TrainerChatPage.vue'), meta: { requiresAuth: true, requiresTrainerAccess: true } },
    { path: '/trainer/rent-history', name: 'trainer-rent-history', component: () => import('./pages/trainer/TrainerRentHistoryPage.vue'), meta: { requiresAuth: true, requiresTrainerAccess: true } },
    { path: '/trainer/notifications', name: 'trainer-notifications', component: () => import('./pages/trainer/TrainerNotificationsPage.vue'), meta: { requiresAuth: true, requiresTrainerAccess: true } },
    { path: '/trainer/profile', name: 'trainer-profile', component: () => import('./pages/trainer/TrainerProfilePage.vue'), meta: { requiresAuth: true, requiresTrainerAccess: true } },
    { path: '/:pathMatch(.*)*', redirect: '/' },
  ],
})

installGuard(router, ['trainer'])
export default router

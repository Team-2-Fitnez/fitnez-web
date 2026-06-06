import { createRouter, createWebHashHistory } from 'vue-router'
import { installGuard } from './router-guard'

const router = createRouter({
  history: createWebHashHistory('/member.html'),
  routes: [
    { path: '/', redirect: '/member/attendance' },
    { path: '/member/dashboard', redirect: '/member/attendance' },
    { path: '/member/workout-plan', name: 'member-workout-plan', component: () => import('./pages/member/MemberWorkoutPlanPage.vue'), meta: { requiresAuth: true, role: 'member' } },
    { path: '/member/meal-plan', name: 'member-meal-plan', component: () => import('./pages/member/MemberMealPlanPage.vue'), meta: { requiresAuth: true, role: 'member' } },
    { path: '/member/hire-trainer', name: 'member-hire-trainer', component: () => import('./pages/member/MemberHireTrainerPage.vue'), meta: { requiresAuth: true, role: 'member' } },
    { path: '/member/schedule', name: 'member-schedule', component: () => import('./pages/member/MemberSchedulePage.vue'), meta: { requiresAuth: true, role: 'member' } },
    { path: '/member/chat', name: 'member-chat', component: () => import('./pages/member/MemberChatPage.vue'), meta: { requiresAuth: true, role: 'member' } },
    { path: '/member/memberships', name: 'member-memberships', component: () => import('./pages/member/MemberMembershipPage.vue'), meta: { requiresAuth: true, role: 'member' } },
    { path: '/member/profile', name: 'member-profile', component: () => import('./pages/member/MemberProfilePage.vue'), meta: { requiresAuth: true, role: 'member' } },
    { path: '/member/notifications', name: 'member-notifications', component: () => import('./pages/member/MemberNotificationsPage.vue'), meta: { requiresAuth: true, role: 'member' } },
    { path: '/member/attendance', name: 'member-attendance', component: () => import('./pages/member/MemberAttendancePage.vue'), meta: { requiresAuth: true, role: 'member' } },
    { path: '/member/payments', name: 'member-payments', component: () => import('./pages/member/MemberPaymentsPage.vue'), meta: { requiresAuth: true, role: 'member' } },
    { path: '/member/classes', name: 'member-classes', component: () => import('./pages/member/MemberClassesPage.vue'), meta: { requiresAuth: true, role: 'member' } },
    { path: '/trainer/daftar', name: 'trainer-apply', component: () => import('./pages/member/MemberTrainerApplyPage.vue'), meta: { requiresAuth: true, role: 'member' } },
    { path: '/:pathMatch(.*)*', redirect: '/' },
  ],
})

installGuard(router, ['member'])
export default router

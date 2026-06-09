export const adminSidebarItems = [
  { label: 'Dashboard', to: '/admin/dashboard', icon: 'Dashboard' },
  { label: 'Payment Review', to: '/admin/prospective-members', icon: 'Review' },
  { label: 'Users', to: '/admin/users', icon: 'Users' },
  { label: 'Payments', to: '/admin/payments', icon: 'Payments' },
  { label: 'Trainer Applications', to: '/admin/trainer-applications', icon: 'Applications' },
  { label: 'Check-In Logs & Reports', to: '/admin/check-in-logs', icon: 'Logs' },
  {
    label: 'Visitor Insights',
    icon: 'VisitorInsights',
    submenu: [
      { label: 'Visitor Analytics', to: '/admin/visitor-analytics', icon: 'Analytics' },
      { label: 'Landing Visitors', to: '/admin/landing-visits', icon: 'Reports' },
    ],
  },
  { label: 'Notifications', to: '/admin/notifications', icon: 'Notifications' },
]

export const memberSidebarItems = [
  { label: 'Attendance', to: '/member/attendance', icon: 'Attendance' },
  { label: 'Workout Plan', to: '/member/workout-plan', icon: 'Workout' },
  { label: 'Meal Plan', to: '/member/meal-plan', icon: 'Meal' },
  {
    label: 'Hire a Trainer',
    icon: 'Trainer',
    submenu: [
      { label: 'Hire Trainer', to: '/member/hire-trainer', icon: 'Hire' },
      { label: 'Register as Trainer', to: '/trainer/apply', icon: 'Apply' },
    ],
  },
  { label: 'Schedule', to: '/member/schedule', icon: 'Schedule' },
  { label: 'Classes', to: '/member/classes', icon: 'Classes' },
  { label: 'Payments', to: '/member/payments', icon: 'Payments' },
  { label: 'Chat', to: '/member/chat', icon: 'Chat' },
  { label: 'Profile', to: '/member/profile', icon: 'Profile' },
  { label: 'Notifications', to: '/member/notifications', icon: 'Notifications' },
]

export const trainerSidebarItems = [
  { label: 'Dashboard', to: '/trainer/dashboard', icon: 'Dashboard' },
  { label: 'Schedule', to: '/trainer/schedule', icon: 'Schedule' },
  { label: 'Classes', to: '/trainer/classes', icon: 'Classes' },
  { label: 'Members', to: '/trainer/members', icon: 'Members' },
  { label: 'Chat', to: '/trainer/chat', icon: 'Chat' },
  { label: 'Trainer Reports', to: '/trainer/rent-history', icon: 'Reports' },
  { label: 'Notifications', to: '/trainer/notifications', icon: 'Notifications' },
  { label: 'Profile', to: '/trainer/profile', icon: 'Profile' },
]

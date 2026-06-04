export const adminSidebarItems = [
  { label: 'Home', to: '/', icon: '🌐', external: true },
  { label: 'Dashboard', to: '/admin/dashboard', icon: '📊' },
  { label: 'Payment Review', to: '/admin/prospective-members', icon: '🧾' },
  { label: 'Users', to: '/admin/users', icon: '👥' },
  { label: 'Payments', to: '/admin/payments', icon: '💰' },
  { label: 'Trainer Applications', to: '/admin/trainer-applications', icon: '📄' },
  { label: 'Landing Visitors', to: '/admin/landing-visits', icon: '👀' },
  { label: 'Auth Activity', to: '/admin/auth-activity', icon: '🔐' },
  { label: 'Admin Reports', to: '/admin/member-reports', icon: '📈' },
  { label: 'Nutrition Monitoring', to: '/admin/nutrition-monitoring', icon: '🥗' },
  { label: 'Notifications', to: '/admin/notifications', icon: '🔔' },
]

export const memberSidebarItems = [
  { label: 'Home', to: '/', icon: '🌐', external: true },
  { label: 'Dashboard', to: '/member/dashboard', icon: '🏠' },
  { label: 'Workout Plan', to: '/member/workout-plan', icon: '🏋️' },
  { label: 'Meal Plan', to: '/member/meal-plan', icon: '🥗' },
  {
    label: 'Hire a Trainer',
    icon: '🤝',
    submenu: [
      { label: 'Hire Trainer', to: '/member/hire-trainer', icon: '🤝' },
      { label: 'Register as Trainer', to: '/trainer/daftar', icon: '⭐' },
    ]
  },
  { label: 'Schedule', to: '/member/schedule', icon: '🗓️' },
  { label: 'Attendance', to: '/member/attendance', icon: '✅' },
  { label: 'Classes', to: '/member/classes', icon: '🏃' },
  { label: 'Payments', to: '/member/payments', icon: '💰' },
  { label: 'Chat', to: '/member/chat', icon: '💬' },
  { label: 'Memberships', to: '/member/memberships', icon: '💳' },
  { label: 'Profile', to: '/member/profile', icon: '👤' },
  { label: 'Notifications', to: '/member/notifications', icon: '🔔' },
]

export const trainerSidebarItems = [
  { label: 'Home', to: '/', icon: '🌐', external: true },
  { label: 'Dashboard', to: '/trainer/dashboard', icon: '📊' },
  { label: 'Attendance', to: '/trainer/attendance', icon: '✅' },
  { label: 'Schedule', to: '/trainer/schedule', icon: '🗓️' },
  { label: 'Classes', to: '/trainer/classes', icon: '🏃' },
  { label: 'Members', to: '/trainer/members', icon: '👥' },
  { label: 'Chat', to: '/trainer/chat', icon: '💬' },
  { label: 'Trainer Reports', to: '/trainer/rent-history', icon: '💰' },
  { label: 'Notifications', to: '/trainer/notifications', icon: '🔔' },
  { label: 'Profile', to: '/trainer/profile', icon: '👤' },
]

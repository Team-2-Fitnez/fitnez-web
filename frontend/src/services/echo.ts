// @ts-expect-error - laravel-echo not installed; kept for legacy reference
import Echo from 'laravel-echo'

let echo: Echo<'pusher'> | null = null

export function getEcho(): Echo<'pusher'> {
  if (echo) return echo

  const token = localStorage.getItem('fitnez_access_token')

  echo = new Echo<'pusher'>({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY || 'fitnez-local-key',
    cluster: import.meta.env.VITE_PUSHER_CLUSTER || 'mt1',
    wsHost: import.meta.env.VITE_PUSHER_HOST || window.location.hostname,
    wsPort: Number(import.meta.env.VITE_PUSHER_PORT || 6001),
    wssPort: Number(import.meta.env.VITE_PUSHER_PORT || 6001),
    forceTLS: false,
    encrypted: false,
    enabledTransports: ['ws', 'wss'],
    disableStats: true,
    authEndpoint: '/api/broadcasting/auth',
    auth: {
      headers: {
        Authorization: `Bearer ${token}`,
        Accept: 'application/json',
      },
    },
  })

  return echo
}

export function destroyEcho() {
  if (echo) {
    echo.disconnect()
    echo = null
  }
}

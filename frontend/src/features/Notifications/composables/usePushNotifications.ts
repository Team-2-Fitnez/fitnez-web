import { ref } from 'vue'
import { http } from '@/shared/api/http'

const VAPID_PUBLIC_KEY = 'BGeB0H7x8E0oJQmNv_wWBMEqUM3HzsiQYmfBg4qIeILP4-KJhtufhFPPFTSEcFNX2RwPTFHVl93q4F2JkG1Z-d8'

export function usePushNotifications() {
  const supported = ref('serviceWorker' in navigator && 'PushManager' in window)
  const subscribed = ref(false)
  const permissionState = ref<NotificationPermission>('default')

  async function init() {
    if (!supported.value) return

    permissionState.value = Notification.permission

    if (Notification.permission === 'granted') {
      const reg = await navigator.serviceWorker.ready
      const sub = await reg.pushManager.getSubscription()
      subscribed.value = !!sub
    }
  }

  async function subscribe() {
    if (!supported.value) return

    let permission = Notification.permission
    if (permission === 'default') {
      permission = await Notification.requestPermission()
      permissionState.value = permission
    }

    if (permission !== 'granted') return

    const reg = await navigator.serviceWorker.register('/sw.js')
    await navigator.serviceWorker.ready

    const sub = await reg.pushManager.subscribe({
      userVisibleOnly: true,
      applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC_KEY),
    })

    const authKey = sub.getKey('auth')
    const p256dhKey = sub.getKey('p256dh')

    await http.post('/push/subscribe', {
      endpoint: sub.endpoint,
      auth_key: authKey ? arrayBufferToBase64(authKey) : '',
      p256dh_key: p256dhKey ? arrayBufferToBase64(p256dhKey) : '',
    })

    subscribed.value = true
  }

  async function unsubscribe() {
    if (!supported.value) return

    const reg = await navigator.serviceWorker.ready
    const sub = await reg.pushManager.getSubscription()
    if (!sub) return

    await http.request('/push/unsubscribe', {
      method: 'DELETE',
      body: JSON.stringify({ endpoint: sub.endpoint }),
    })
    await sub.unsubscribe()
    subscribed.value = false
  }

  return { supported, subscribed, permissionState, init, subscribe, unsubscribe }
}

function urlBase64ToUint8Array(base64String: string): Uint8Array<ArrayBuffer> {
  const padding = '='.repeat((4 - (base64String.length % 4)) % 4)
  const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/')
  const rawData = window.atob(base64)
  return Uint8Array.from([...rawData].map((char) => char.charCodeAt(0)))
}

function arrayBufferToBase64(buffer: ArrayBuffer): string {
  const bytes = new Uint8Array(buffer)
  let binary = ''
  for (let i = 0; i < bytes.byteLength; i++) {
    binary += String.fromCharCode(bytes[i])
  }
  return btoa(binary)
}

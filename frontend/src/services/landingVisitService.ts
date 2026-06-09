import { detectIncognito } from 'detectincognitojs'
import { landingVisitApi } from '../api/landingVisitApi'
import type { LandingVisitPayload } from '../types/landingVisit'

type BrowserInfo = {
  name: string
  engine: string
}

type PrivateModeInfo = {
  detected: boolean | null
  confidence: 'high' | 'low' | 'unknown'
  source: string
}

function shortId() {
  const randomUUID = globalThis.crypto?.randomUUID?.bind(globalThis.crypto)

  if (randomUUID) {
    return (randomUUID().split('-')[0] || 'RANDOMID').toUpperCase()
  }

  return Math.random().toString(36).slice(2, 10).toUpperCase()
}

function makeVisitorId() {
  return `VIS-${shortId()}`
}

function makeSessionId() {
  return `SES-${shortId()}`
}

function canPersistLocalStorage() {
  try {
    const key = '__fitnez_storage_test__'
    localStorage.setItem(key, '1')
    localStorage.removeItem(key)
    return true
  } catch {
    return false
  }
}

async function privateModeInfo(): Promise<PrivateModeInfo> {
  try {
    const result = await detectIncognito()

    return {
      detected: Boolean(result.isPrivate),
      confidence: result.isPrivate ? 'high' : 'low',
      source: `detectincognitojs:${result.browserName || 'unknown'}`,
    }
  } catch {
    return {
      detected: null,
      confidence: 'unknown',
      source: 'detection_failed_or_browser_blocked',
    }
  }
}

function contextFromPrivateMode(info: PrivateModeInfo) {
  if (info.detected === true) {
    return {
      code: 'private_or_incognito_detected',
      label: 'Private/Incognito Detected',
      storage: 'temporary_or_restricted',
    }
  }

  if (info.detected === false) {
    return {
      code: 'private_mode_not_detected',
      label: 'Private Mode Not Detected',
      storage: canPersistLocalStorage() ? 'localStorage' : 'restricted',
    }
  }

  return {
    code: 'detection_limited',
    label: 'Storage Restricted / Detection Limited',
    storage: canPersistLocalStorage() ? 'localStorage' : 'restricted',
  }
}

async function browserInfo(): Promise<BrowserInfo> {
  const nav = navigator as Navigator & {
    brave?: {
      isBrave?: () => Promise<boolean>
    }
    userAgentData?: {
      brands?: Array<{ brand: string; version: string }>
    }
  }

  try {
    if (nav.brave?.isBrave && await nav.brave.isBrave()) {
      return {
        name: 'Brave',
        engine: 'Chromium',
      }
    }
  } catch {
    // continue fallback detection
  }

  const brands = nav.userAgentData?.brands?.map((item) => item.brand).join(' ') || ''

  if (/Microsoft Edge|Edge/i.test(brands) || /Edg\//.test(navigator.userAgent)) {
    return { name: 'Edge', engine: 'Chromium' }
  }

  if (/Opera|OPR/i.test(brands) || /OPR\//.test(navigator.userAgent)) {
    return { name: 'Opera', engine: 'Chromium' }
  }

  if (/Firefox\//.test(navigator.userAgent)) {
    return { name: 'Firefox', engine: 'Gecko' }
  }

  if (/Chrome|Chromium/i.test(brands) || /Chrome\//.test(navigator.userAgent)) {
    return { name: 'Chrome', engine: 'Chromium' }
  }

  if (/Safari\//.test(navigator.userAgent) && !/Chrome\//.test(navigator.userAgent)) {
    return { name: 'Safari', engine: 'WebKit' }
  }

  return { name: 'Unknown', engine: 'Unknown' }
}

function visitorUuid(contextCode: string) {
  /*
   * If private/incognito is detected, use sessionStorage.
   * It survives refresh and same-window navigation, but disappears after close.
   */
  if (contextCode === 'private_or_incognito_detected') {
    const key = 'fitnez_private_visitor_uuid_v3'
    let value = sessionStorage.getItem(key)

    if (!value || !value.startsWith('VIS-')) {
      value = makeVisitorId()
      sessionStorage.setItem(key, value)
    }

    return value
  }

  /*
   * If private mode is not detected, use localStorage so same browser/profile
   * is counted once per day even across tabs.
   */
  if (canPersistLocalStorage()) {
    const key = 'fitnez_regular_visitor_uuid_v3'
    let value = localStorage.getItem(key)

    if (!value || !value.startsWith('VIS-')) {
      value = makeVisitorId()
      localStorage.setItem(key, value)
    }

    return value
  }

  /*
   * Fallback for storage-restricted browsers.
   */
  const key = 'fitnez_restricted_visitor_uuid_v3'
  let value = sessionStorage.getItem(key)

  if (!value || !value.startsWith('VIS-')) {
    value = makeVisitorId()
    sessionStorage.setItem(key, value)
  }

  return value
}

function sessionUuid() {
  const key = 'fitnez_landing_session_uuid_v3'
  let value = sessionStorage.getItem(key)

  if (!value || !value.startsWith('SES-')) {
    value = makeSessionId()
    sessionStorage.setItem(key, value)
  }

  return value
}

function queryParams() {
  return Object.fromEntries(new URLSearchParams(window.location.search).entries())
}

async function payload(): Promise<LandingVisitPayload> {
  const privateInfo = await privateModeInfo()
  const context = contextFromPrivateMode(privateInfo)
  const browser = await browserInfo()

  return {
    visitor_uuid: visitorUuid(context.code),
    session_uuid: sessionUuid(),

    browser_context: context.code,
    browser_context_label: context.label,

    private_mode_detected: privateInfo.detected,
    private_mode_confidence: privateInfo.confidence,
    private_mode_source: privateInfo.source,

    client_browser_name: browser.name,
    client_browser_engine: browser.engine,

    referrer: document.referrer || '',
    landing_url: window.location.href,
    route_path: window.location.pathname,
    query_params: queryParams(),
    locale: navigator.language,
    timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
    screen_width: window.screen.width,
    screen_height: window.screen.height,
    viewport_width: window.innerWidth,
    viewport_height: window.innerHeight,
  }
}

export const landingVisitService = {
  async trackCurrentLandingPage() {
    try {
      await landingVisitApi.track(await payload())
    } catch {
      // Tracking must never block landing page UX.
    }
  },

  startLandingTrackingHeartbeat() {
    this.trackCurrentLandingPage()

    const timer = window.setInterval(() => {
      if (document.visibilityState === 'visible') {
        this.trackCurrentLandingPage()
      }
    }, 30000)

    return () => window.clearInterval(timer)
  },
}

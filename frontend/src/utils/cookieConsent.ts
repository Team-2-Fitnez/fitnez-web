export type CookieCategory = 'essential' | 'analytics' | 'marketing' | 'preferences'

export type CookieConsentState = {
  anonymousId: string
  categories: Record<CookieCategory, boolean>
  consentedAt: string
  updatedAt: string
  version: string
}

export const COOKIE_CONSENT_KEY = 'fitnez_cookie_consent'
export const COOKIE_ANON_ID_KEY = 'fitnez_cookie_anon_id'
export const CONSENT_VERSION = '2026-05-20'

export const defaultConsentCategories: Record<CookieCategory, boolean> = {
  essential: true,
  analytics: false,
  marketing: false,
  preferences: false,
}

export function getAnonymousId(): string {
  const existing = localStorage.getItem(COOKIE_ANON_ID_KEY)
  if (existing) return existing

  const id =
    typeof crypto !== 'undefined' && 'randomUUID' in crypto
      ? crypto.randomUUID()
      : `anon_${Date.now()}_${Math.random().toString(16).slice(2)}`

  localStorage.setItem(COOKIE_ANON_ID_KEY, id)
  return id
}

export function getStoredConsent(): CookieConsentState | null {
  const rawConsent = localStorage.getItem(COOKIE_CONSENT_KEY)
  if (!rawConsent) return null

  try {
    return JSON.parse(rawConsent) as CookieConsentState
  } catch {
    localStorage.removeItem(COOKIE_CONSENT_KEY)
    return null
  }
}

export function hasConsentFor(category: Exclude<CookieCategory, 'essential'>): boolean {
  const consent = getStoredConsent()
  return Boolean(consent?.categories?.[category])
}

export function saveConsent(
  categories: Record<CookieCategory, boolean>,
  options?: { logToServer?: boolean },
): CookieConsentState {
  const now = new Date().toISOString()

  const safeCategories: Record<CookieCategory, boolean> = {
    essential: true,
    analytics: Boolean(categories.analytics),
    marketing: Boolean(categories.marketing),
    preferences: Boolean(categories.preferences),
  }

  const previous = getStoredConsent()
  const state: CookieConsentState = {
    anonymousId: getAnonymousId(),
    categories: safeCategories,
    consentedAt: previous?.consentedAt ?? now,
    updatedAt: now,
    version: CONSENT_VERSION,
  }

  localStorage.setItem(COOKIE_CONSENT_KEY, JSON.stringify(state))

  window.dispatchEvent(
    new CustomEvent('fitnez-cookie-consent-changed', {
      detail: state,
    }),
  )

  if (options?.logToServer !== false) {
    void logConsentToServer(state)
  }

  return state
}

export function clearConsent(): void {
  localStorage.removeItem(COOKIE_CONSENT_KEY)
  window.dispatchEvent(new CustomEvent('fitnez-cookie-consent-cleared'))
}

export async function logConsentToServer(consent: CookieConsentState): Promise<void> {
  try {
    await fetch('/api/cookie-consents', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
      },
      credentials: 'same-origin',
      body: JSON.stringify({
        anonymous_id: consent.anonymousId,
        consent_version: consent.version,
        categories: consent.categories,
        consented_at: consent.consentedAt,
        updated_at: consent.updatedAt,
      }),
    })
  } catch (error) {
    console.warn('Consent berhasil disimpan di browser, tetapi gagal dikirim ke server.', error)
  }
}

/**
 * Prior Consent / Default Blocked:
 * Panggil fungsi ini untuk memuat script non-esensial hanya setelah user memberi izin.
 *
 * Contoh:
 * loadScriptAfterConsent('analytics', 'https://www.googletagmanager.com/gtag/js?id=G-XXXX')
 */
export function loadScriptAfterConsent(
  category: Exclude<CookieCategory, 'essential'>,
  src: string,
  attributes: Record<string, string> = {},
): void {
  const load = () => {
    if (!hasConsentFor(category)) return
    if (document.querySelector(`script[data-fitnez-cookie-src="${src}"]`)) return

    const script = document.createElement('script')
    script.src = src
    script.async = true
    script.dataset.fitnezCookieSrc = src

    Object.entries(attributes).forEach(([key, value]) => {
      script.setAttribute(key, value)
    })

    document.head.appendChild(script)
  }

  load()
  window.addEventListener('fitnez-cookie-consent-changed', load)
}

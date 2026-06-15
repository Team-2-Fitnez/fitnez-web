export type ApiResponse<T> = {
  success: boolean
  message: string
  data: T
  errors?: Record<string, unknown>
}

type HttpRequestOptions = RequestInit & {
  meta?: {
    skipGlobalLoading?: boolean
    confirmationRetried?: boolean
  }
}

export class HttpClient {
  constructor(private readonly baseUrl: string) {}

  token(): string | null {
    return localStorage.getItem('fitnez_access_token')
  }

  streamUrl(path: string): string {
    return this.url(path)
  }

  url(path: string): string {
    const token = this.token()
    const separator = path.includes('?') ? '&' : '?'
    return `${this.baseUrl}${path}${token ? `${separator}token=${encodeURIComponent(token)}` : ''}`
  }

  private withConfirmation(options: HttpRequestOptions): HttpRequestOptions {
    if (options.body instanceof FormData) {
      const body = new FormData()
      options.body.forEach((value, key) => body.append(key, value))
      body.set('confirmed', 'true')
      return { ...options, body, meta: { ...(options.meta ?? {}), confirmationRetried: true } }
    }

    let body: Record<string, unknown> = {}

    if (typeof options.body === 'string' && options.body.trim() !== '') {
      try {
        const parsed = JSON.parse(options.body)
        if (parsed && typeof parsed === 'object' && !Array.isArray(parsed)) {
          body = parsed
        }
      } catch {
        body = {}
      }
    }

    return {
      ...options,
      body: JSON.stringify({ ...body, confirmed: true }),
      meta: { ...(options.meta ?? {}), confirmationRetried: true },
    }
  }

  private confirmationMessage(payload: any): string {
    const action = payload?.errors?.action ? `\nAksi: ${payload.errors.action}` : ''
    const target = payload?.errors?.target ? `\nTarget: ${payload.errors.target}` : ''
    return `${payload?.message || 'Tindakan ini membutuhkan konfirmasi.'}${action}${target}\n\nLanjutkan?`
  }

  async request<T>(path: string, options: HttpRequestOptions = {}): Promise<ApiResponse<T>> {
    const headers = new Headers(options.headers || {})
    headers.set('Accept', 'application/json')

    if (!(options.body instanceof FormData)) {
      headers.set('Content-Type', 'application/json')
    }

    const token = this.token()
    if (token) headers.set('Authorization', `Bearer ${token}`)

    let response: Response

    try {
      response = await fetch(`${this.baseUrl}${path}`, {
        ...options,
        headers,
        credentials: 'include',
      })
    } catch (error) {
      throw Object.assign(
        new Error(`Cannot connect to Fitnez API at ${this.baseUrl}. Check Docker containers and Vite proxy.`),
        { originalError: error },
      )
    }

    const payload = await response.json().catch(() => ({
      success: false,
      message: `Server returned non-JSON response. HTTP ${response.status}`,
      data: null,
    }))

    const needsConfirmation =
      response.status === 409 &&
      payload?.errors?.confirmation_required === true &&
      options.meta?.confirmationRetried !== true &&
      typeof window !== 'undefined'

    if (needsConfirmation) {
      const accepted = window.confirm(this.confirmationMessage(payload))
      if (accepted) {
        return this.request<T>(path, this.withConfirmation(options))
      }
    }

    if (!response.ok) {
      throw Object.assign(new Error(payload.message || `Request failed with HTTP ${response.status}`), {
        status: response.status,
        payload,
      })
    }

    return payload
  }

  get<T>(path: string): Promise<ApiResponse<T>> {
    return this.request<T>(path)
  }

  post<T>(path: string, body?: unknown): Promise<ApiResponse<T>> {
    return this.request<T>(path, {
      method: 'POST',
      body: body instanceof FormData ? body : JSON.stringify(body ?? {}),
    })
  }

  put<T>(path: string, body?: unknown): Promise<ApiResponse<T>> {
    return this.request<T>(path, { method: 'PUT', body: JSON.stringify(body ?? {}) })
  }

  patch<T>(path: string, body?: unknown): Promise<ApiResponse<T>> {
    return this.request<T>(path, { method: 'PATCH', body: JSON.stringify(body ?? {}) })
  }

  delete<T>(path: string, body?: unknown): Promise<ApiResponse<T>> {
    return this.request<T>(path, { method: 'DELETE', body: JSON.stringify(body ?? {}) })
  }

  async downloadBlob(path: string, defaultFilename = 'export.xlsx'): Promise<void> {
    const token = this.token()
    const headers: Record<string, string> = {
      Accept: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    }
    if (token) headers['Authorization'] = `Bearer ${token}`

    const response = await fetch(`${this.baseUrl}${path}`, { headers })
    if (!response.ok) throw new Error('Download failed')

    const blob = await response.blob()
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = defaultFilename
    a.click()
    URL.revokeObjectURL(url)
  }
}

export const http = new HttpClient(import.meta.env.VITE_API_BASE_URL || '/api')

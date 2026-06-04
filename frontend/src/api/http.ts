export type ApiResponse<T> = {
  success: boolean
  message: string
  data: T
  errors?: Record<string, string[]>
}

type HttpRequestOptions = RequestInit & {
  meta?: {
    skipGlobalLoading?: boolean
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

  delete<T>(path: string): Promise<ApiResponse<T>> {
    return this.request<T>(path, { method: 'DELETE' })
  }
}

export const http = new HttpClient(import.meta.env.VITE_API_BASE_URL || '/api')

interface ApiError {
  response?: { data?: { message?: string } }
  payload?: { message?: string }
  message?: string
}

export function getApiErrorMessage(error: unknown, fallback = 'Request failed'): string {
  const e = error as ApiError
  return e?.response?.data?.message || e?.payload?.message || e?.message || fallback
}

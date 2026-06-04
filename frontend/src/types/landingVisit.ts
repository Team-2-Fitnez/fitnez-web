export type LandingVisitPayload = {
  visitor_uuid: string
  session_uuid: string

  browser_context?: string
  browser_context_label?: string
  client_browser_name?: string
  client_browser_engine?: string

  referrer?: string
  landing_url?: string
  route_path?: string
  query_params?: Record<string, string>
  locale?: string
  timezone?: string
  screen_width?: number
  screen_height?: number
  viewport_width?: number
  viewport_height?: number

  private_mode_detected?: boolean | null
  private_mode_confidence?: string
  private_mode_source?: string
}

export type LandingVisit = {
  id: number
  visitor_uuid: string
  session_uuid: string
  user_id?: number | null
  ip_address?: string | null
  browser_name?: string | null
  browser_version?: string | null
  os_name?: string | null
  device_type?: string | null
  browser_context?: string | null
  browser_context_label?: string | null
  client_browser_name?: string | null
  client_browser_engine?: string | null
  private_mode_detected?: boolean | null
  private_mode_confidence?: string | null
  private_mode_source?: string | null
  referrer?: string | null
  landing_url?: string | null
  route_path?: string | null
  locale?: string | null
  timezone?: string | null
  visited_at: string
  last_seen_at?: string | null
  page_view_count?: number | null
}

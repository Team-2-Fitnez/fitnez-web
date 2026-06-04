import { http } from './http'
import type { BrowserTrackingPayload, HeartbeatResult, LeaderResult } from '../types/browserTracking'
export const browserTrackingApi = { heartbeat:(p:BrowserTrackingPayload)=>http.post<HeartbeatResult>('/browser/heartbeat',p), electLeader:(p:Pick<BrowserTrackingPayload,'device_uuid'|'tab_id'|'context_type'>)=>http.post<LeaderResult>('/browser/elect-leader',p), releaseLeader:(p:Pick<BrowserTrackingPayload,'device_uuid'|'tab_id'|'context_type'>)=>http.post<null>('/browser/release-leader',p) }

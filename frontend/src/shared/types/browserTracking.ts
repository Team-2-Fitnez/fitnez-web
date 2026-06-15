export type BrowserContextType = 'normal' | 'private'
export type BrowserTrackingPayload = { device_uuid:string; tab_id:string; context_type:BrowserContextType; platform:'web'; browser?:string; user_agent_hash?:string; route?:string }
export type HeartbeatResult = { tab_id:string; device_uuid:string; context_type:string; is_leader:boolean; last_heartbeat_at:string }
export type LeaderResult = { leader_tab_id:string; current_tab_id:string; is_current_tab_leader:boolean; context_type:string }

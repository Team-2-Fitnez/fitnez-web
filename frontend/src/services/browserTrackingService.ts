import { browserTrackingApi } from '../api/browserTrackingApi'
import type { BrowserContextType, BrowserTrackingPayload } from '../types/browserTracking'
import { getNormalDeviceUuid, getPrivateDeviceUuid, getTabId, hashUserAgent } from '../utils/deviceId'
import { storageWorks } from '../utils/storage'
export function detectBrowserContext():BrowserContextType{ return storageWorks(localStorage) ? 'normal' : 'private' }
export function createBrowserIdentity(){ const contextType=detectBrowserContext(); return {contextType,deviceUuid:contextType==='normal'?getNormalDeviceUuid():getPrivateDeviceUuid(),tabId:getTabId(contextType==='normal'?'tab':'private_tab')} }
export function createHeartbeatPayload(deviceUuid:string,tabId:string,contextType:BrowserContextType):BrowserTrackingPayload{ return {device_uuid:deviceUuid,tab_id:tabId,context_type:contextType,platform:'web',browser:navigator.userAgent.slice(0,100),user_agent_hash:hashUserAgent(navigator.userAgent),route:window.location.pathname} }
export const browserTrackingService = { heartbeat:browserTrackingApi.heartbeat, electLeader:browserTrackingApi.electLeader, releaseLeader:browserTrackingApi.releaseLeader }

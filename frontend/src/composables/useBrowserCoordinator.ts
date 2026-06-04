import { onBeforeUnmount, onMounted } from 'vue'
import { browserTrackingService, createBrowserIdentity, createHeartbeatPayload } from '../services/browserTrackingService'
import { useBrowserTrackingStore } from '../stores/browserTrackingStore'
export function useBrowserCoordinator(){
  const store=useBrowserTrackingStore(); const identity=createBrowserIdentity(); const channel='BroadcastChannel' in window ? new BroadcastChannel('fitnez-browser') : null; let heartbeatTimer:number|undefined; let electionTimer:number|undefined; let localLeaderTimer:number|undefined; store.setIdentity(identity.deviceUuid,identity.tabId,identity.contextType);
  function visible(){ return document.visibilityState==='visible' }
  function localElectLeader(){ const key=`fitnez_leader_${identity.contextType}_${identity.deviceUuid}`; const now=Date.now(); let leader:null|{tabId:string;expiresAt:number}=null; try{ const raw=localStorage.getItem(key); leader=raw?JSON.parse(raw):null }catch{} if(!leader||leader.expiresAt<now||leader.tabId===identity.tabId){ localStorage.setItem(key,JSON.stringify({tabId:identity.tabId,expiresAt:now+8000})); store.setLeader(true); channel?.postMessage({type:'leader',tabId:identity.tabId}); return } store.setLeader(leader.tabId===identity.tabId) }
  async function backendHeartbeat(){ if(!localStorage.getItem('fitnez_access_token')) return; try{ const r=await browserTrackingService.heartbeat(createHeartbeatPayload(identity.deviceUuid,identity.tabId,identity.contextType)); store.setLeader(Boolean(r.data.is_leader)) }catch{} }
  async function backendElectLeader(){ if(!localStorage.getItem('fitnez_access_token')) return; try{ const r=await browserTrackingService.electLeader({device_uuid:identity.deviceUuid,tab_id:identity.tabId,context_type:identity.contextType}); store.setLeader(r.data.is_current_tab_leader) }catch{} }
  function start(){ localElectLeader(); localLeaderTimer=window.setInterval(()=>{ if(visible()) localElectLeader() },3000); heartbeatTimer=window.setInterval(()=>{ if(visible()&&store.isLeader) backendHeartbeat() },15000); electionTimer=window.setInterval(()=>{ if(visible()) backendElectLeader() },20000) }
  function stop(){ if(heartbeatTimer) window.clearInterval(heartbeatTimer); if(electionTimer) window.clearInterval(electionTimer); if(localLeaderTimer) window.clearInterval(localLeaderTimer) }
  function onVisibilityChange(){ if(visible()){ localElectLeader(); backendElectLeader() } }
  function onBeforeUnload(){ if(store.isLeader&&localStorage.getItem('fitnez_access_token')) browserTrackingService.releaseLeader({device_uuid:identity.deviceUuid,tab_id:identity.tabId,context_type:identity.contextType}).catch(()=>null) }
  channel?.addEventListener('message',e=>{ if(e.data?.type==='leader'&&e.data.tabId!==identity.tabId) store.setLeader(false) })
  onMounted(()=>{ document.addEventListener('visibilitychange',onVisibilityChange); window.addEventListener('beforeunload',onBeforeUnload); start() })
  onBeforeUnmount(()=>{ document.removeEventListener('visibilitychange',onVisibilityChange); window.removeEventListener('beforeunload',onBeforeUnload); stop(); channel?.close() })
  return { browserTrackingStore:store }
}

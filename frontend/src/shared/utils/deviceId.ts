export function randomId(prefix:string){ return `${prefix}_${crypto.randomUUID?.() || Math.random().toString(36).slice(2)}` }
export function getNormalDeviceUuid(){ const k='fitnez_device_uuid'; let id=localStorage.getItem(k); if(!id){ id=randomId('device'); localStorage.setItem(k,id) } return id }
export function getPrivateDeviceUuid(){ const k='fitnez_private_device_uuid'; let id=sessionStorage.getItem(k); if(!id){ id=randomId('private_device'); sessionStorage.setItem(k,id) } return id }
export function getTabId(prefix='tab'){ const k=`fitnez_${prefix}_id`; let id=sessionStorage.getItem(k); if(!id){ id=randomId(prefix); sessionStorage.setItem(k,id) } return id }
export function hashUserAgent(input:string){ let h=0; for(let i=0;i<input.length;i++){ h=(h<<5)-h+input.charCodeAt(i); h|=0 } return String(h) }

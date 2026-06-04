import { defineStore } from 'pinia'
import type { BrowserContextType } from '../types/browserTracking'
export const useBrowserTrackingStore = defineStore('browserTracking',{ state:()=>({deviceUuid:'',tabId:'',contextType:'normal' as BrowserContextType,isLeader:false}), actions:{ setIdentity(deviceUuid:string,tabId:string,contextType:BrowserContextType){ this.deviceUuid=deviceUuid; this.tabId=tabId; this.contextType=contextType }, setLeader(value:boolean){ this.isLeader=value } } })

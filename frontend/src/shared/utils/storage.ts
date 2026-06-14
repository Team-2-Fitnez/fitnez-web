export function storageWorks(storage:Storage){ try{ const k='__fitnez_storage_test__'; storage.setItem(k,'1'); storage.removeItem(k); return true } catch { return false } }

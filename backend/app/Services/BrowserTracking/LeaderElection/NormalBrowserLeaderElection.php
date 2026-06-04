<?php
namespace App\Services\BrowserTracking\LeaderElection;
use App\Models\BrowserTab;
use App\Models\User;
use Illuminate\Support\Facades\DB;
class NormalBrowserLeaderElection implements LeaderElectionStrategy {
    public function elect(?User $user, string $deviceUuid, string $tabId, string $contextType): BrowserTab { return DB::transaction(function() use($user,$deviceUuid,$tabId,$contextType) { $fresh=now()->subSeconds(20); $leader=BrowserTab::query()->where('device_uuid',$deviceUuid)->where('context_type',$contextType)->where('is_leader',true)->where('last_heartbeat_at','>=',$fresh)->lockForUpdate()->first(); if($leader) return $leader; BrowserTab::query()->where('device_uuid',$deviceUuid)->where('context_type',$contextType)->update(['is_leader'=>false]); $tab=BrowserTab::query()->firstOrCreate(['device_uuid'=>$deviceUuid,'tab_id'=>$tabId,'context_type'=>$contextType],['user_id'=>$user?->id,'last_heartbeat_at'=>now()]); $tab->forceFill(['user_id'=>$user?->id,'is_leader'=>true,'last_heartbeat_at'=>now()])->save(); return $tab; }); }
}

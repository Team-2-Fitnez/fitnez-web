<?php
namespace App\Services\BrowserTracking;
use App\Models\BrowserTab;
use App\Models\User;
class TabTracker {
    public function touch(array $data, ?User $user): BrowserTab { return BrowserTab::query()->updateOrCreate(['device_uuid'=>$data['device_uuid'],'tab_id'=>$data['tab_id'],'context_type'=>$data['context_type']],['user_id'=>$user?->id,'route'=>$data['route'] ?? null,'last_heartbeat_at'=>now()]); }
    public function releaseLeader(string $deviceUuid, string $tabId, string $contextType): void { BrowserTab::query()->where('device_uuid',$deviceUuid)->where('tab_id',$tabId)->where('context_type',$contextType)->update(['is_leader'=>false]); }
}

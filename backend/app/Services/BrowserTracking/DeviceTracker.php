<?php
namespace App\Services\BrowserTracking;
use App\Models\User;
use App\Models\UserDevice;
class DeviceTracker { public function touch(array $data, ?User $user): UserDevice { return UserDevice::query()->updateOrCreate(['device_uuid'=>$data['device_uuid'],'platform'=>$data['platform'] ?? 'web'],['user_id'=>$user?->id,'browser'=>$data['browser'] ?? null,'user_agent_hash'=>$data['user_agent_hash'] ?? null,'last_seen_at'=>now()]); } }

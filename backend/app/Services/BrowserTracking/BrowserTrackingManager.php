<?php
namespace App\Services\BrowserTracking;
use App\Models\User;
class BrowserTrackingManager {
    public function __construct(private readonly BrowserTrackingService $service) {}
    public function heartbeat(array $data, ?User $user): array { return $this->service->heartbeat($data,$user); }
    public function electLeader(array $data, ?User $user): array { return $this->service->electLeader($data,$user); }
    public function releaseLeader(array $data): void { $this->service->releaseLeader($data); }
}

<?php
namespace App\Services\BrowserTracking\LeaderElection;
use App\Models\BrowserTab;
use App\Models\User;
class PrivateBrowserLeaderElection implements LeaderElectionStrategy {
    public function __construct(private readonly NormalBrowserLeaderElection $normal) {}
    public function elect(?User $user, string $deviceUuid, string $tabId, string $contextType): BrowserTab { return $this->normal->elect($user,$deviceUuid,$tabId,'private'); }
}

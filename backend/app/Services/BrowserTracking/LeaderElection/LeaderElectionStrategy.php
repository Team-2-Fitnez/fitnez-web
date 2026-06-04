<?php
namespace App\Services\BrowserTracking\LeaderElection;
use App\Models\BrowserTab;
use App\Models\User;
interface LeaderElectionStrategy { public function elect(?User $user, string $deviceUuid, string $tabId, string $contextType): BrowserTab; }

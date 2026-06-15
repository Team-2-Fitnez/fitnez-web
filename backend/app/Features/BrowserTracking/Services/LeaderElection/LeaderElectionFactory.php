<?php
namespace App\Features\BrowserTracking\Services\LeaderElection;
class LeaderElectionFactory { public function make(string $contextType): LeaderElectionStrategy { return match($contextType) { 'private','incognito','ignore'=>app(PrivateBrowserLeaderElection::class), default=>app(NormalBrowserLeaderElection::class) }; } }

<?php
namespace App\Features\BrowserTracking\Actions;
use App\Features\BrowserTracking\Services\BrowserTrackingManager;
class ReleaseLeaderTabAction { public function __construct(private readonly BrowserTrackingManager $manager) {} public function handle(array $data): void { $this->manager->releaseLeader($data); } }

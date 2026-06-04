<?php
namespace App\Actions\BrowserTracking;
use App\Services\BrowserTracking\BrowserTrackingManager;
class ReleaseLeaderTabAction { public function __construct(private readonly BrowserTrackingManager $manager) {} public function handle(array $data): void { $this->manager->releaseLeader($data); } }

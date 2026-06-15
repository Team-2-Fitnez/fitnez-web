<?php
namespace App\Actions\BrowserTracking;
use App\Services\BrowserTracking\BrowserTrackingManager;
use Illuminate\Http\Request;
class ElectLeaderTabAction { public function __construct(private readonly BrowserTrackingManager $manager) {} public function handle(array $data, Request $request): array { return $this->manager->electLeader($data,$request->user()); } }

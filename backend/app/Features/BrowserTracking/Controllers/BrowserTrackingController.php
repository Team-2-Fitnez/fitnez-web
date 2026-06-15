<?php
namespace App\Features\BrowserTracking\Controllers;

use App\Http\Controllers\Controller;

use App\Features\BrowserTracking\Actions\ElectLeaderTabAction;
use App\Features\BrowserTracking\Actions\ReleaseLeaderTabAction;
use App\Features\BrowserTracking\Actions\StoreBrowserHeartbeatAction;
use App\Features\BrowserTracking\Requests\BrowserHeartbeatRequest;
use App\Features\BrowserTracking\Requests\ElectLeaderTabRequest;
use App\Features\BrowserTracking\Requests\ReleaseLeaderTabRequest;
use App\Support\ApiResponse;
class BrowserTrackingController extends Controller {
    public function heartbeat(BrowserHeartbeatRequest $request, StoreBrowserHeartbeatAction $action) { return ApiResponse::success('Browser heartbeat accepted.', $action->handle($request->validated(), $request)); }
    public function electLeader(ElectLeaderTabRequest $request, ElectLeaderTabAction $action) { return ApiResponse::success('Leader tab election completed.', $action->handle($request->validated(), $request)); }
    public function releaseLeader(ReleaseLeaderTabRequest $request, ReleaseLeaderTabAction $action) { $action->handle($request->validated()); return ApiResponse::success('Leader tab released.'); }
}

<?php
namespace App\Http\Controllers;
use App\Actions\BrowserTracking\ElectLeaderTabAction;
use App\Actions\BrowserTracking\ReleaseLeaderTabAction;
use App\Actions\BrowserTracking\StoreBrowserHeartbeatAction;
use App\Http\Requests\BrowserTracking\BrowserHeartbeatRequest;
use App\Http\Requests\BrowserTracking\ElectLeaderTabRequest;
use App\Http\Requests\BrowserTracking\ReleaseLeaderTabRequest;
use App\Support\ApiResponse;
class BrowserTrackingController extends Controller {
    public function heartbeat(BrowserHeartbeatRequest $request, StoreBrowserHeartbeatAction $action) { return ApiResponse::success('Browser heartbeat accepted.', $action->handle($request->validated(), $request)); }
    public function electLeader(ElectLeaderTabRequest $request, ElectLeaderTabAction $action) { return ApiResponse::success('Leader tab election completed.', $action->handle($request->validated(), $request)); }
    public function releaseLeader(ReleaseLeaderTabRequest $request, ReleaseLeaderTabAction $action) { $action->handle($request->validated()); return ApiResponse::success('Leader tab released.'); }
}

<?php

namespace App\Http\Controllers\Api;

use App\Features\HireTrainer\Controllers\Admin\TrainerApplicationReviewController;
use App\Features\HireTrainer\Controllers\Admin\TrainerManagementController;
use App\Features\HireTrainer\Controllers\TrainerApplicationController;
use App\Features\HireTrainer\Requests\Admin\ApproveTrainerApplicationRequest;
use App\Features\HireTrainer\Requests\Admin\RejectTrainerApplicationRequest;
use App\Features\HireTrainer\Requests\Admin\StoreTrainerRequest;
use App\Features\HireTrainer\Requests\Admin\UpdateTrainerRequest;
use App\Features\HireTrainer\Requests\Trainer\StoreTrainerApplicationRequest;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexTableRequest;
use App\Models\TrainerApplication;
use App\Models\TrainerDetail;
use Illuminate\Http\Request;

class TrainerController extends Controller
{
    public function publicList(Request $request)
    {
        return app(TrainerManagementController::class)->publicList($request);
    }

    public function applicationStatus(Request $request)
    {
        return app(TrainerApplicationController::class)->status($request);
    }

    public function storeApplication(StoreTrainerApplicationRequest $request)
    {
        return app(TrainerApplicationController::class)->store($request);
    }

    public function enterWorkspace(Request $request)
    {
        return app(TrainerApplicationController::class)->enterWorkspace($request);
    }

    public function leaveWorkspace(Request $request)
    {
        return app(TrainerApplicationController::class)->leaveWorkspace($request);
    }

    public function index(IndexTableRequest $request)
    {
        return app(TrainerManagementController::class)->index($request);
    }

    public function store(StoreTrainerRequest $request)
    {
        return app(TrainerManagementController::class)->store($request);
    }

    public function update(UpdateTrainerRequest $request, TrainerDetail $trainer)
    {
        return app(TrainerManagementController::class)->update($request, $trainer);
    }

    public function destroy(Request $request, TrainerDetail $trainer)
    {
        return app(TrainerManagementController::class)->destroy($request, $trainer);
    }

    public function applications(IndexTableRequest $request)
    {
        return app(TrainerApplicationReviewController::class)->index($request);
    }

    public function approveApplication(ApproveTrainerApplicationRequest $request, TrainerApplication $application)
    {
        return app(TrainerApplicationReviewController::class)->approve($request, $application);
    }

    public function rejectApplication(RejectTrainerApplicationRequest $request, TrainerApplication $application)
    {
        return app(TrainerApplicationReviewController::class)->reject($request, $application);
    }

    public function downloadApplicationDocument(TrainerApplication $application, string $type)
    {
        return app(TrainerApplicationReviewController::class)->download($application, $type);
    }

    public function streamApplicationDocument(TrainerApplication $application, string $type)
    {
        return app(TrainerApplicationReviewController::class)->stream($application, $type);
    }
}

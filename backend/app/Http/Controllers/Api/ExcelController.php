<?php

namespace App\Http\Controllers\Api;

use App\Features\Attendance\Controllers\ExcelExportController;
use App\Features\Attendance\Controllers\ExcelImportController;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ExcelController extends Controller
{
    public function upload(Request $request)
    {
        return app(ExcelImportController::class)->upload($request);
    }

    public function landingVisits(Request $request)
    {
        return app(ExcelExportController::class)->landingVisits($request);
    }

    public function authActivity(Request $request)
    {
        return app(ExcelExportController::class)->authActivity($request);
    }

    public function memberReports(Request $request)
    {
        return app(ExcelExportController::class)->memberReports($request);
    }

    public function memberReportsSse(Request $request)
    {
        return app(ExcelExportController::class)->memberReportsSse($request);
    }

    public function memberReportsDownload(string $filename)
    {
        return app(ExcelExportController::class)->memberReportsDownload($filename);
    }

    public function payments(Request $request)
    {
        return app(ExcelExportController::class)->payments($request);
    }

    public function attendance(Request $request)
    {
        return app(ExcelExportController::class)->attendance($request);
    }

    public function users(Request $request)
    {
        return app(ExcelExportController::class)->users($request);
    }

    public function nutritionMonitoring(Request $request)
    {
        return app(ExcelExportController::class)->nutritionMonitoring($request);
    }
}

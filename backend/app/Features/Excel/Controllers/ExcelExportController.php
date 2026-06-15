<?php

namespace App\Features\Excel\Controllers;

use App\Http\Controllers\Controller;

use App\Models\LandingPageVisit;
use App\Models\SystemLog;
use App\Models\Payment;
use App\Models\Attendance;
use App\Models\User;
use App\Models\FoodLog;
use App\Models\MealPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExcelExportController extends Controller
{
    private function cols(): array
    {
        return range('A', 'Z');
    }

    private function download(Spreadsheet $spreadsheet, string $filename)
    {
        $writer = new Xlsx($spreadsheet);
        $response = new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        });
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', "attachment; filename=\"$filename\"");
        $response->headers->set('Cache-Control', 'max-age=0');
        return $response;
    }

    private function writeRow($sheet, int $row, array $values): void
    {
        $cols = $this->cols();
        foreach ($values as $i => $val) {
            $sheet->setCellValue($cols[$i] . $row, $val);
        }
    }

    public function landingVisits(Request $request)
    {
        $rows = LandingPageVisit::query()
            ->when($request->start_date, fn($q, $d) => $q->whereDate('visit_date', '>=', $d))
            ->when($request->end_date, fn($q, $d) => $q->whereDate('visit_date', '<=', $d))
            ->orderByDesc('last_seen_at')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Landing Visitors');

        $this->writeRow($sheet, 1, ['Visitor UUID', 'Browser', 'Device', 'OS', 'IP Address', 'Page Views', 'Last Seen', 'Visit Date']);

        $r = 2;
        foreach ($rows as $row) {
            $this->writeRow($sheet, $r++, [
                $row->visitor_uuid,
                $row->browser_name ?? '',
                $row->device_type ?? '',
                $row->os_name ?? '',
                $row->ip_address ?? '',
                $row->page_view_count ?? 0,
                $row->last_seen_at ?? '',
                $row->visit_date ?? '',
            ]);
        }

        return $this->download($spreadsheet, 'landing-visitors.xlsx');
    }

    public function authActivity(Request $request)
    {
        $logs = SystemLog::query()
            ->with('user')
            ->when($request->action_type, fn($q, $a) => $q->where('action_type', $a))
            ->when($request->start_date, fn($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($request->end_date, fn($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->orderByDesc('created_at')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Auth Activity');

        $this->writeRow($sheet, 1, ['User', 'Email', 'Action', 'Description', 'Table Affected', 'Record ID', 'Timestamp']);

        $r = 2;
        foreach ($logs as $log) {
            $this->writeRow($sheet, $r++, [
                $log->user->full_name ?? '-',
                $log->user->email ?? '-',
                $log->action_type,
                $log->description ?? '',
                $log->table_affected ?? '',
                $log->record_id ?? '',
                $log->created_at ?? '',
            ]);
        }

        return $this->download($spreadsheet, 'auth-activity.xlsx');
    }

    public function payments(Request $request)
    {
        $payments = Payment::query()
            ->with('user')
            ->when($request->payment_status, fn($q, $s) => $q->where('payment_status', $s))
            ->when($request->payment_type, fn($q, $t) => $q->where('payment_type', $t))
            ->when($request->start_date, fn($q, $d) => $q->whereDate('payment_date', '>=', $d))
            ->when($request->end_date, fn($q, $d) => $q->whereDate('payment_date', '<=', $d))
            ->orderByDesc('payment_date')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Payments');

        $this->writeRow($sheet, 1, ['Invoice', 'Member', 'Email', 'Type', 'Amount', 'Method', 'Status', 'Date']);

        $r = 2;
        foreach ($payments as $p) {
            $this->writeRow($sheet, $r++, [
                $p->invoice_number,
                $p->user->full_name ?? '-',
                $p->user->email ?? '-',
                $p->payment_type,
                (float) $p->amount,
                $p->payment_method ?? '',
                $p->payment_status ?? 'pending',
                $p->payment_date ?? '',
            ]);
        }

        return $this->download($spreadsheet, 'payments.xlsx');
    }

    public function attendance(Request $request)
    {
        $rows = Attendance::query()
            ->with('user')
            ->when($request->attendance_type, fn($q, $t) => $q->where('attendance_type', $t))
            ->when($request->start_date, fn($q, $d) => $q->whereDate('check_in_time', '>=', $d))
            ->when($request->end_date, fn($q, $d) => $q->whereDate('check_in_time', '<=', $d))
            ->orderByDesc('check_in_time')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Attendance');

        $this->writeRow($sheet, 1, ['Member', 'Email', 'Type', 'Check-In', 'Check-Out', 'Status']);

        $r = 2;
        foreach ($rows as $row) {
            $this->writeRow($sheet, $r++, [
                $row->user->full_name ?? '-',
                $row->user->email ?? '-',
                $row->attendance_type,
                $row->check_in_time ?? '',
                $row->check_out_time ?? '',
                $row->check_out_time ? 'Completed' : 'Active',
            ]);
        }

        return $this->download($spreadsheet, 'attendance.xlsx');
    }

    public function memberReportsSse(Request $request)
    {
        $jobId = (string) str()->uuid();
        $endDate = $request->end_date ? Carbon::parse($request->end_date)->endOfDay() : now()->endOfDay();
        $startDate = $request->start_date ? Carbon::parse($request->start_date)->startOfDay() : $endDate->copy()->subMonth()->startOfDay();

        $response = new StreamedResponse(function () use ($jobId, $startDate, $endDate) {
            $this->writeSseProgress($jobId, 5, 'processing', 'Starting report generation...');

            $payments = \App\Models\Payment::query()
                ->with('user')
                ->whereBetween('payment_date', [$startDate, $endDate])
                ->orderByDesc('payment_date')
                ->get();
            $this->writeSseProgress($jobId, 20, 'processing', 'Payments data loaded.');

            $attendance = \App\Models\Attendance::query()
                ->with(['user', 'booking.trainer'])
                ->whereBetween('check_in_time', [$startDate, $endDate])
                ->orderByDesc('check_in_time')
                ->get();
            $this->writeSseProgress($jobId, 40, 'processing', 'Attendance data loaded.');

            $spreadsheet = new Spreadsheet();
            $summarySheet = $spreadsheet->getActiveSheet();
            $summarySheet->setTitle('Summary');
            $this->writeRow($summarySheet, 1, ['Fitnez Admin Report']);
            $this->writeRow($summarySheet, 3, ['Period', $startDate->toDateString() . ' to ' . $endDate->toDateString()]);
            $this->writeRow($summarySheet, 4, ['Total Revenue', (float) $payments->sum('amount')]);
            $this->writeRow($summarySheet, 5, ['Total Transactions', $payments->count()]);
            $this->writeRow($summarySheet, 6, ['Successful Transactions', $payments->where('payment_status', 'paid')->count()]);
            $this->writeRow($summarySheet, 7, ['Pending Transactions', $payments->where('payment_status', 'pending')->count()]);
            $this->writeRow($summarySheet, 8, ['Total Attendance', $attendance->count()]);
            $this->writeRow($summarySheet, 9, ['Attendance Today', $attendance->filter(fn($row) => optional($row->check_in_time)->isToday())->count()]);
            $this->writeSseProgress($jobId, 60, 'processing', 'Summary sheet built.');

            $paymentSheet = $spreadsheet->createSheet();
            $paymentSheet->setTitle('Payments');
            $this->writeRow($paymentSheet, 1, ['Invoice', 'Member', 'Email', 'Type', 'Amount', 'Method', 'Status', 'Date']);
            $r = 2;
            foreach ($payments as $payment) {
                $this->writeRow($paymentSheet, $r++, [
                    $payment->invoice_number, $payment->user->full_name ?? '-', $payment->user->email ?? '-',
                    $payment->payment_type, (float) $payment->amount, $payment->payment_method ?? '',
                    $payment->payment_status ?? 'pending', $payment->payment_date ?? '',
                ]);
            }
            $this->writeSseProgress($jobId, 75, 'processing', 'Payment sheet built.');

            $attendanceSheet = $spreadsheet->createSheet();
            $attendanceSheet->setTitle('Attendance');
            $this->writeRow($attendanceSheet, 1, ['Member', 'Email', 'Type', 'Check-In', 'Check-Out', 'Booking', 'Trainer', 'Status']);
            $r = 2;
            foreach ($attendance as $row) {
                $this->writeRow($attendanceSheet, $r++, [
                    $row->user->full_name ?? '-', $row->user->email ?? '-', $row->attendance_type,
                    $row->check_in_time ?? '', $row->check_out_time ?? '',
                    $row->booking ? (($row->booking->start_date ?? '') . ' ' . ($row->booking->session_time ?? '')) : '', $row->booking->trainer->full_name ?? '',
                    $row->check_out_time ? 'Completed' : 'Active',
                ]);
            }
            $this->writeSseProgress($jobId, 90, 'processing', 'Attendance sheet built, finalizing...');

            $filename = 'member-reports-' . $jobId . '.xlsx';
            $tempPath = sys_get_temp_dir() . '/' . $filename;
            $writer = new Xlsx($spreadsheet);
            $writer->save($tempPath);

            $downloadUrl = url('/admin/export/member-reports/download/' . $filename);
            Cache::put("sse_download_{$jobId}", $tempPath, 300);
            Cache::put("sse_download_url_{$jobId}", $downloadUrl, 300);

            $this->writeSseProgress($jobId, 100, 'completed', 'Report ready!', ['download_url' => $downloadUrl]);
        });

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('X-Accel-Buffering', 'no');
        $response->headers->set('Connection', 'keep-alive');

        return $response;
    }

    private function writeSseProgress(string $jobId, int $progress, string $status, string $message, array $extra = []): void
    {
        Cache::put("sse_progress_{$jobId}", $progress, 300);
        Cache::put("sse_status_{$jobId}", $status, 300);
        Cache::put("sse_message_{$jobId}", $message, 300);

        $data = array_merge(['progress' => $progress, 'status' => $status, 'message' => $message], $extra);
        echo "event: progress\n";
        echo 'data: ' . json_encode($data) . "\n\n";
        ob_flush();
        flush();
    }

    public function memberReportsDownload(string $filename)
    {
        $tempPath = sys_get_temp_dir() . '/' . basename($filename);
        if (!file_exists($tempPath)) {
            abort(404, 'Report file not found or expired.');
        }
        return response()->download($tempPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function memberReports(Request $request)
    {
        $endDate = $request->end_date ? Carbon::parse($request->end_date)->endOfDay() : now()->endOfDay();
        $startDate = $request->start_date ? Carbon::parse($request->start_date)->startOfDay() : $endDate->copy()->subMonth()->startOfDay();

        $payments = Payment::query()
            ->with('user')
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->orderByDesc('payment_date')
            ->get();

        $attendance = Attendance::query()
            ->with(['user', 'booking.trainer'])
            ->whereBetween('check_in_time', [$startDate, $endDate])
            ->orderByDesc('check_in_time')
            ->get();

        $spreadsheet = new Spreadsheet();
        $summarySheet = $spreadsheet->getActiveSheet();
        $summarySheet->setTitle('Summary');
        $this->writeRow($summarySheet, 1, ['Fitnez Admin Report']);
        $this->writeRow($summarySheet, 3, ['Period', $startDate->toDateString() . ' to ' . $endDate->toDateString()]);
        $this->writeRow($summarySheet, 4, ['Total Revenue', (float) $payments->sum('amount')]);
        $this->writeRow($summarySheet, 5, ['Total Transactions', $payments->count()]);
        $this->writeRow($summarySheet, 6, ['Successful Transactions', $payments->where('payment_status', 'paid')->count()]);
        $this->writeRow($summarySheet, 7, ['Pending Transactions', $payments->where('payment_status', 'pending')->count()]);
        $this->writeRow($summarySheet, 8, ['Total Attendance', $attendance->count()]);
        $this->writeRow($summarySheet, 9, ['Attendance Today', $attendance->filter(fn($row) => optional($row->check_in_time)->isToday())->count()]);

        $paymentSheet = $spreadsheet->createSheet();
        $paymentSheet->setTitle('Payments');
        $this->writeRow($paymentSheet, 1, ['Invoice', 'Member', 'Email', 'Type', 'Amount', 'Method', 'Status', 'Date']);
        $r = 2;
        foreach ($payments as $payment) {
            $this->writeRow($paymentSheet, $r++, [
                $payment->invoice_number,
                $payment->user->full_name ?? '-',
                $payment->user->email ?? '-',
                $payment->payment_type,
                (float) $payment->amount,
                $payment->payment_method ?? '',
                $payment->payment_status ?? 'pending',
                $payment->payment_date ?? '',
            ]);
        }

        $attendanceSheet = $spreadsheet->createSheet();
        $attendanceSheet->setTitle('Attendance');
        $this->writeRow($attendanceSheet, 1, ['Member', 'Email', 'Type', 'Check-In', 'Check-Out', 'Booking', 'Trainer', 'Status']);
        $r = 2;
        foreach ($attendance as $row) {
            $this->writeRow($attendanceSheet, $r++, [
                $row->user->full_name ?? '-',
                $row->user->email ?? '-',
                $row->attendance_type,
                $row->check_in_time ?? '',
                $row->check_out_time ?? '',
                $row->booking ? (($row->booking->start_date ?? '') . ' ' . ($row->booking->session_time ?? '')) : '',
                $row->booking->trainer->full_name ?? '',
                $row->check_out_time ? 'Completed' : 'Active',
            ]);
        }

        return $this->download($spreadsheet, 'member-reports.xlsx');
    }

    public function nutritionMonitoring(Request $request)
    {
        $mealPlans = MealPlan::query()->with('user')->get()->groupBy('user_id');

        $userIds = $mealPlans->keys();

        $foodLogs = FoodLog::query()
            ->selectRaw('user_id, sum(calories) as total_calories')
            ->whereIn('user_id', $userIds)
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Nutrition Monitoring');

        $this->writeRow($sheet, 1, ['Member', 'Email', 'Daily Limit (kcal)', 'Total Consumption (kcal)', 'Progress (%)', 'Status']);

        $r = 2;
        foreach ($mealPlans as $userId => $plans) {
            $user = $plans->first()->user;
            $totalCalories = (int) ($foodLogs[$userId]->total_calories ?? 0);
            $dailyLimit = (int) $plans->max('daily_limit');
            $pct = $dailyLimit > 0 ? round(($totalCalories / $dailyLimit) * 100, 1) : 0;
            $status = $dailyLimit <= 0 ? 'Limit not set' : ($pct >= 100 ? 'Exceeds limit' : ($pct >= 80 ? 'Almost full' : 'Normal'));

            $this->writeRow($sheet, $r++, [
                $user->full_name ?? '-',
                $user->email ?? '-',
                $dailyLimit,
                $totalCalories,
                $pct,
                $status,
            ]);
        }

        return $this->download($spreadsheet, 'nutrition-monitoring.xlsx');
    }

    public function users(Request $request)
    {
        $users = User::query()
            ->with('role')
            ->when($request->role, fn($q, $r) => $q->whereHas('role', fn($qr) => $qr->where('name', $r)))
            ->when($request->status === 'active', fn($q) => $q->where('is_active', true))
            ->when($request->status === 'inactive', fn($q) => $q->where('is_active', false))
            ->orderByDesc('created_at')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Users');

        $this->writeRow($sheet, 1, ['Name', 'Email', 'Phone', 'Role', 'Status', 'Registered At']);

        $r = 2;
        foreach ($users as $u) {
            $this->writeRow($sheet, $r++, [
                $u->full_name,
                $u->email,
                $u->phone ?? '',
                $u->role->name ?? 'member',
                $u->is_active ? 'Active' : 'Inactive',
                $u->created_at ?? '',
            ]);
        }

        return $this->download($spreadsheet, 'users.xlsx');
    }
}

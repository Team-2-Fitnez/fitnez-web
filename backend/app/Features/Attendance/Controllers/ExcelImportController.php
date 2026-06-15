<?php

namespace App\Features\Attendance\Controllers;

use App\Http\Controllers\Controller;

use App\Models\Role;
use App\Models\User;
use App\Models\WorkoutPlan;
use App\Models\TrainerBooking;
use App\Support\SseProgress;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ExcelImportController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'type' => 'required|in:schedules,members,workouts',
        ]);

        $file = $request->file('file');
        $jobId = 'import_' . uniqid();

        SseProgress::start($jobId);

        try {
            $spreadsheet = IOFactory::load($file->getPathname());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            if (empty($rows) || count($rows) < 2) {
                SseProgress::fail($jobId, 'File is empty or contains headers only');
                return response()->json(['success' => false, 'message' => 'Empty file', 'job_id' => $jobId]);
            }

            $header = array_shift($rows);
            $header = array_map('trim', $header);
            $total = count($rows);
            $imported = 0;
            $errors = [];

            foreach ($rows as $i => $row) {
                $row = array_map('trim', $row);
                $data = array_combine($header, $row);
                $progress = (int) (($i + 1) / $total * 100);
                $rowNum = $i + 2;

                try {
                    switch ($request->type) {
                        case 'members':
                            $email = $data['email'] ?? '';
                            if (empty($email)) {
                                $errors[] = "Row $rowNum: email is empty";
                                break;
                            }
                            if (User::where('email', $email)->exists()) {
                                $errors[] = "Row $rowNum: email $email is already registered";
                                break;
                            }
                            User::create([
                                'email' => $email,
                                'full_name' => $data['full_name'] ?? $data['name'] ?? '',
                                'phone' => $data['phone'] ?? '',
                                'password_hash' => Hash::make($data['password'] ?? 'FitnezDefault@2026'),
                                'role_id' => Role::where('name', 'member')->value('id'),
                                'is_active' => true,
                            ]);
                            $imported++;
                            break;

                        case 'workouts':
                            $userEmail = $data['email'] ?? $data['user_email'] ?? '';
                            $user = User::where('email', $userEmail)->first();
                            if (!$user) {
                                $errors[] = "Row $rowNum: user with email $userEmail not found";
                                break;
                            }
                            WorkoutPlan::create([
                                'user_id' => $user->id,
                                'name' => $data['name'] ?? $data['exercise'] ?? '',
                                'category' => $data['category'] ?? 'General',
                                'date' => $data['date'] ?? now()->toDateString(),
                                'day' => $data['day'] ?? '',
                                'set' => (int) ($data['set'] ?? 0),
                                'weight' => (float) ($data['weight'] ?? 0),
                                'reps' => (int) ($data['reps'] ?? 0),
                                'duration' => (int) ($data['duration'] ?? 0),
                                'completed' => filter_var($data['completed'] ?? false, FILTER_VALIDATE_BOOLEAN),
                            ]);
                            $imported++;
                            break;

                        case 'schedules':
                            $memberEmail = $data['member_email'] ?? $data['email'] ?? '';
                            $trainerEmail = $data['trainer_email'] ?? '';
                            $member = User::where('email', $memberEmail)->first();
                            $trainer = User::where('email', $trainerEmail)->first();
                            if (!$member) {
                                $errors[] = "Row $rowNum: member $memberEmail not found";
                                break;
                            }
                            if (!$trainer) {
                                $errors[] = "Row $rowNum: trainer $trainerEmail not found";
                                break;
                            }
                            $startDate = Carbon::parse($data['start_date'] ?? $data['booking_date'] ?? $data['date'] ?? now()->toDateString());
                            $endDate = isset($data['end_date']) ? Carbon::parse($data['end_date']) : $startDate->copy()->addWeeks(4)->subDay();
                            $sessionsPerWeek = (int) ($data['sessions_per_week'] ?? 3);
                            $sessionDays = $data['session_days'] ?? 'monday,wednesday,friday';
                            $sessionDays = is_array($sessionDays)
                                ? $sessionDays
                                : array_values(array_filter(array_map(fn ($day) => strtolower(trim($day)), explode(',', $sessionDays))));
                            $totalSessions = (int) ($data['total_sessions'] ?? ($sessionsPerWeek * 4));
                            $basePrice = (float) ($data['base_price_per_session'] ?? 0);
                            $memberPrice = (float) ($data['member_price_per_session'] ?? ($basePrice > 0 ? $basePrice * 1.5 : 0));

                            TrainerBooking::create([
                                'member_id' => $member->id,
                                'trainer_id' => $trainer->id,
                                'start_date' => $startDate->toDateString(),
                                'end_date' => $endDate->toDateString(),
                                'sessions_per_week' => $sessionsPerWeek,
                                'session_days' => $sessionDays,
                                'session_time' => $data['session_time'] ?? $data['start_time'] ?? '09:00',
                                'member_notes' => $data['notes'] ?? $data['member_notes'] ?? '',
                                'status' => $data['status'] ?? TrainerBooking::STATUS_PENDING,
                                'base_price_per_session' => $basePrice,
                                'member_price_per_session' => $memberPrice,
                                'total_member_price' => (float) ($data['total_member_price'] ?? ($memberPrice * $totalSessions)),
                                'total_trainer_price' => (float) ($data['total_trainer_price'] ?? ($basePrice * $totalSessions)),
                                'total_sessions' => $totalSessions,
                            ]);
                            $imported++;
                            break;
                    }
                } catch (\Throwable $e) {
                    $errors[] = "Row $rowNum: " . $e->getMessage();
                }

                SseProgress::update($jobId, $progress, "Processing row " . ($i + 1) . " of $total");
                usleep(50000);
            }

            $message = "Successfully imported $imported of $total records";
            if (!empty($errors)) {
                $message .= '. ' . count($errors) . ' rows failed: ' . implode('; ', array_slice($errors, 0, 5));
                if (count($errors) > 5) {
                    $message .= '... and ' . (count($errors) - 5) . ' other errors';
                }
            }

            SseProgress::complete($jobId, $message);
            return response()->json([
                'success' => $imported > 0,
                'message' => $message,
                'job_id' => $jobId,
                'imported' => $imported,
                'total' => $total,
                'errors' => $errors,
            ]);
        } catch (\Throwable $e) {
            SseProgress::fail($jobId, $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'job_id' => $jobId]);
        }
    }
}

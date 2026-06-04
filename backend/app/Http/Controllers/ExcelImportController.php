<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Models\WorkoutPlan;
use App\Models\TrainerBooking;
use App\Support\SseProgress;
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
                            TrainerBooking::create([
                                'member_id' => $member->id,
                                'trainer_id' => $trainer->id,
                                'booking_date' => $data['booking_date'] ?? $data['date'] ?? now()->toDateString(),
                                'start_time' => $data['start_time'] ?? '09:00',
                                'end_time' => $data['end_time'] ?? '10:00',
                                'session_type' => ($data['session_type'] ?? 'online') === 'offline' ? 'offline' : 'online',
                                'location' => $data['location'] ?? 'Gym Utama',
                                'member_notes' => $data['notes'] ?? '',
                                'status' => TrainerBooking::STATUS_PENDING,
                                'total_price' => (float) ($data['total_price'] ?? 0),
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

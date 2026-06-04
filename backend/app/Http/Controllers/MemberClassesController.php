<?php

namespace App\Http\Controllers;

use App\Models\Classes;
use App\Models\ClassMember;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class MemberClassesController extends Controller
{
    public function index()
    {
        $classes = Classes::query()
            ->where('status', 'active')
            ->withCount('members as current_participants')
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get()
            ->map(function ($c) {
                return [
                    'id' => $c->id,
                    'name' => $c->name,
                    'description' => $c->description,
                    'trainer_name' => optional($c->trainer)->full_name ?? '-',
                    'day_of_week' => (string) $c->day_of_week,
                    'start_time' => substr($c->start_time, 0, 5),
                    'end_time' => substr($c->end_time, 0, 5),
                    'max_participants' => (int) $c->max_participants,
                    'current_participants' => (int) $c->current_participants,
                    'status' => $c->status,
                ];
            });

        return ApiResponse::success('Classes loaded.', $classes);
    }

    public function join(Request $request, $classId)
    {
        $class = Classes::query()
            ->where('id', $classId)
            ->where('status', 'active')
            ->firstOrFail();

        $memberCount = ClassMember::where('class_id', $classId)->count();

        if ($memberCount >= $class->max_participants) {
            return ApiResponse::error('Class is already full.', [], 422);
        }

        $existing = ClassMember::query()
            ->where('class_id', $classId)
            ->where('user_id', $request->user()->id)
            ->exists();

        if ($existing) {
            return ApiResponse::error('You are already registered in this class.', [], 422);
        }

        ClassMember::create([
            'class_id' => $classId,
            'user_id' => $request->user()->id,
        ]);

        return ApiResponse::success('Successfully joined the class.', [], 201);
    }

    public function myClasses(Request $request)
    {
        $classes = ClassMember::query()
            ->where('user_id', $request->user()->id)
            ->with('class.trainer')
            ->get()
            ->map(function ($cm) {
                $c = $cm->class;
                return [
                    'id' => $c->id,
                    'name' => $c->name,
                    'description' => $c->description,
                    'trainer_name' => optional($c->trainer)->full_name ?? '-',
                    'day_of_week' => (string) $c->day_of_week,
                    'start_time' => substr($c->start_time, 0, 5),
                    'end_time' => substr($c->end_time, 0, 5),
                    'status' => $c->status,
                    'joined_at' => $cm->created_at,
                ];
            });

        return ApiResponse::success('My classes loaded.', $classes);
    }
}

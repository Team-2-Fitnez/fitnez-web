<?php

namespace App\Http\Controllers;

use App\Support\ApiResponse;

class LandingController extends Controller
{
    public function index()
    {
        return ApiResponse::success('Fitnez landing content.', [
            'brand' => 'Fitnez',
            'headline' => 'Train smarter with Fitnez.',
            'subtitle' => 'A gym platform for members, trainers, and admins.',
            'features' => [
                'Prospective member registration',
                'Secure direct login',
                'Password recovery with email OTP',
                'Browser tab coordination to reduce polling load',
            ],
            'cta' => [
                'register' => '/register',
                'member_login' => '/login/member',
            ],
        ]);
    }
}

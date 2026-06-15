<?php

namespace App\Http\Controllers\Api;

use App\Features\Schedule\Controllers\MemberClassesController;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ClassController extends Controller
{
    public function index(Request $request)
    {
        return app(MemberClassesController::class)->index($request);
    }

    public function join(Request $request, $id)
    {
        return app(MemberClassesController::class)->join($request, $id);
    }

    public function myClasses(Request $request)
    {
        return app(MemberClassesController::class)->myClasses($request);
    }
}

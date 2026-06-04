<?php
namespace App\Actions\Auth;
use App\Services\Auth\AuthManager;
use Illuminate\Http\Request;
class GetAuthenticatedUserAction { public function __construct(private readonly AuthManager $auth) {} public function handle(Request $request): array { return $this->auth->me($request); } }

<?php
namespace App\Services\Auth\Login;
use InvalidArgumentException;
class LoginStrategyFactory { public function make(string $channel): LoginStrategy { return match($channel) { 'web'=>app(WebLoginStrategy::class), 'mobile'=>app(MobileLoginStrategy::class), default=>throw new InvalidArgumentException("Unsupported login channel: {$channel}") }; } }

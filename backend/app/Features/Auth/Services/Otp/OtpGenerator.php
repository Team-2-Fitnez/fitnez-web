<?php
namespace App\Features\Auth\Services\Otp;
class OtpGenerator { public function numeric(int $digits = 6): string { return (string)random_int(10 ** ($digits-1), (10 ** $digits)-1); } }

<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$reg = App\Models\ProspectiveMemberRegistration::latest('id')->first();
if(!$reg) { echo "no reg"; exit; }
echo "Reg status: " . $reg->status . "\n";
try {
    $action = app(App\Actions\Auth\CreateMemberFromApprovedRegistrationAction::class);
    $user = $action->handle($reg);
    echo "Success. User id: " . $user->id;
} catch(\Throwable $e) {
    echo "Error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine();
}

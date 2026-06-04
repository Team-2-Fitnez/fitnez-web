<?php

namespace App\Providers;

use App\Listeners\BroadcastViaSocketio;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Event::subscribe(BroadcastViaSocketio::class);
    }
}

<?php

namespace App\Providers;

use App\Services\Booking\PriceCalculator;
use App\Services\Booking\PriceCalculatorFactory;
use App\Services\Chat\MessageFormatter;
use App\Services\Chat\MessageFormatterFactory;
use App\Services\Trainer\ApplicationValidator;
use App\Services\Trainer\ApplicationValidatorFactory;
use Illuminate\Support\ServiceProvider;

class FitnezServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PriceCalculator::class, function () {
            return PriceCalculatorFactory::create(
                request()->input('session_type', 'online')
            );
        });

        $this->app->bind(MessageFormatter::class, function () {
            return MessageFormatterFactory::create(
                request()->input('format_type', 'regular')
            );
        });

        $this->app->bind(ApplicationValidator::class, function () {
            return ApplicationValidatorFactory::create(
                config('app.trainer_validation_mode', 'standard')
            );
        });
    }

    public function boot(): void
    {
        //
    }
}

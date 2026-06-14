<?php

namespace App\Providers;

use App\Features\HireTrainer\Services\Booking\PriceCalculator;
use App\Features\HireTrainer\Services\Booking\PriceCalculatorFactory;
use App\Features\Chat\Services\Chat\MessageFormatter;
use App\Features\Chat\Services\Chat\MessageFormatterFactory;
use App\Features\HireTrainer\Services\Trainer\ApplicationValidator;
use App\Features\HireTrainer\Services\Trainer\ApplicationValidatorFactory;
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

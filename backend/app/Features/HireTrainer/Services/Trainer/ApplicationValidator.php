<?php

namespace App\Features\HireTrainer\Services\Trainer;

use Illuminate\Http\UploadedFile;

interface ApplicationValidator
{
    public function validate(
        string $specialization,
        int $experienceYears,
        ?UploadedFile $cv,
        ?UploadedFile $certificate
    ): ?string;
}


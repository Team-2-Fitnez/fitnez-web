<?php

namespace App\Services\Trainer;

use Illuminate\Http\UploadedFile;

class StrictApplicationValidator implements ApplicationValidator
{
    public function validate(
        string $specialization,
        int $experienceYears,
        ?UploadedFile $cv,
        ?UploadedFile $certificate
    ): ?string {
        if (empty(trim($specialization))) {
            return 'Specialization is required.';
        }
        if ($experienceYears < 2) {
            return 'Minimum 2 years of experience required for strict validation.';
        }
        if (!$cv || !$cv->isValid()) {
            return 'CV file is required.';
        }
        if ($cv->getClientMimeType() !== 'application/pdf') {
            return 'CV must be a PDF file.';
        }
        if ($cv->getSize() > 3 * 1024 * 1024) {
            return 'CV file must be under 3MB.';
        }
        if (!$certificate || !$certificate->isValid()) {
            return 'Certificate file is required.';
        }
        if ($certificate->getClientMimeType() !== 'application/pdf') {
            return 'Certificate must be a PDF file.';
        }
        if ($certificate->getSize() > 3 * 1024 * 1024) {
            return 'Certificate file must be under 3MB.';
        }
        return null;
    }
}

<?php

namespace App\Services\Trainer;

use Illuminate\Http\UploadedFile;

class StandardApplicationValidator implements ApplicationValidator
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
        if ($experienceYears < 0 || $experienceYears > 50) {
            return 'Experience years must be between 0 and 50.';
        }
        if (!$cv || !$cv->isValid()) {
            return 'CV file is required and must be valid.';
        }
        if ($cv->getClientMimeType() !== 'application/pdf') {
            return 'CV must be a PDF file.';
        }
        if ($cv->getSize() > 5 * 1024 * 1024) {
            return 'CV file must be under 5MB.';
        }
        if (!$certificate || !$certificate->isValid()) {
            return 'Certificate file is required and must be valid.';
        }
        if ($certificate->getClientMimeType() !== 'application/pdf') {
            return 'Certificate must be a PDF file.';
        }
        if ($certificate->getSize() > 5 * 1024 * 1024) {
            return 'Certificate file must be under 5MB.';
        }
        return null;
    }
}

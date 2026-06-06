<?php

namespace Tests\Unit;

use App\Services\Trainer\StandardApplicationValidator;
use App\Services\Trainer\StrictApplicationValidator;
use App\Services\Trainer\ApplicationValidatorFactory;
use PHPUnit\Framework\TestCase;

class ApplicationValidatorTest extends TestCase
{
    private function createUploadedFile(string $name, int $sizeKb, string $mimeType)
    {
        $tmpPath = tempnam(sys_get_temp_dir(), 'val');
        file_put_contents($tmpPath, str_repeat('x', $sizeKb * 1024));

        return new \Illuminate\Http\UploadedFile(
            $tmpPath, $name, $mimeType, null, true
        );
    }

    // ========================================================================
    //  STRATEGY PATTERN — StandardApplicationValidator
    // ========================================================================

    public function test_standard_validator_passes_with_valid_data(): void
    {
        $validator = new StandardApplicationValidator();
        $cv = $this->createUploadedFile('cv.pdf', 1, 'application/pdf');
        $certificate = $this->createUploadedFile('cert.pdf', 1, 'application/pdf');

        $error = $validator->validate('Strength Training', 3, $cv, $certificate);

        $this->assertNull($error);
    }

    public function test_standard_validator_rejects_empty_specialization(): void
    {
        $validator = new StandardApplicationValidator();
        $cv = $this->createUploadedFile('cv.pdf', 1, 'application/pdf');
        $certificate = $this->createUploadedFile('cert.pdf', 1, 'application/pdf');

        $error = $validator->validate('', 3, $cv, $certificate);

        $this->assertEquals('Specialization is required.', $error);
    }

    public function test_standard_validator_rejects_negative_experience(): void
    {
        $validator = new StandardApplicationValidator();
        $cv = $this->createUploadedFile('cv.pdf', 1, 'application/pdf');
        $certificate = $this->createUploadedFile('cert.pdf', 1, 'application/pdf');

        $error = $validator->validate('Yoga', -1, $cv, $certificate);

        $this->assertEquals('Experience years must be between 0 and 50.', $error);
    }

    public function test_standard_validator_rejects_experience_above_50(): void
    {
        $validator = new StandardApplicationValidator();
        $cv = $this->createUploadedFile('cv.pdf', 1, 'application/pdf');
        $certificate = $this->createUploadedFile('cert.pdf', 1, 'application/pdf');

        $error = $validator->validate('Yoga', 51, $cv, $certificate);

        $this->assertEquals('Experience years must be between 0 and 50.', $error);
    }

    public function test_standard_validator_accepts_zero_experience(): void
    {
        $validator = new StandardApplicationValidator();
        $cv = $this->createUploadedFile('cv.pdf', 1, 'application/pdf');
        $certificate = $this->createUploadedFile('cert.pdf', 1, 'application/pdf');

        $error = $validator->validate('Pilates', 0, $cv, $certificate);

        $this->assertNull($error);
    }

    public function test_standard_validator_accepts_max_experience(): void
    {
        $validator = new StandardApplicationValidator();
        $cv = $this->createUploadedFile('cv.pdf', 1, 'application/pdf');
        $certificate = $this->createUploadedFile('cert.pdf', 1, 'application/pdf');

        $error = $validator->validate('Pilates', 50, $cv, $certificate);

        $this->assertNull($error);
    }

    public function test_standard_validator_rejects_non_pdf_cv(): void
    {
        $validator = new StandardApplicationValidator();
        $cv = $this->createUploadedFile('cv.png', 1, 'image/png');
        $certificate = $this->createUploadedFile('cert.pdf', 1, 'application/pdf');

        $error = $validator->validate('Strength', 3, $cv, $certificate);

        $this->assertEquals('CV must be a PDF file.', $error);
    }

    public function test_standard_validator_rejects_cv_over_5mb(): void
    {
        $validator = new StandardApplicationValidator();
        $cv = $this->createUploadedFile('cv.pdf', 6 * 1024, 'application/pdf');
        $certificate = $this->createUploadedFile('cert.pdf', 1, 'application/pdf');

        $error = $validator->validate('Strength', 3, $cv, $certificate);

        $this->assertEquals('CV file must be under 5MB.', $error);
    }

    public function test_standard_validator_rejects_non_pdf_certificate(): void
    {
        $validator = new StandardApplicationValidator();
        $cv = $this->createUploadedFile('cv.pdf', 1, 'application/pdf');
        $certificate = $this->createUploadedFile('cert.jpg', 1, 'image/jpeg');

        $error = $validator->validate('Strength', 3, $cv, $certificate);

        $this->assertEquals('Certificate must be a PDF file.', $error);
    }

    public function test_standard_validator_rejects_certificate_over_5mb(): void
    {
        $validator = new StandardApplicationValidator();
        $cv = $this->createUploadedFile('cv.pdf', 1, 'application/pdf');
        $certificate = $this->createUploadedFile('cert.pdf', 6 * 1024, 'application/pdf');

        $error = $validator->validate('Strength', 3, $cv, $certificate);

        $this->assertEquals('Certificate file must be under 5MB.', $error);
    }

    public function test_standard_validator_rejects_missing_cv(): void
    {
        $validator = new StandardApplicationValidator();
        $certificate = $this->createUploadedFile('cert.pdf', 1, 'application/pdf');

        $error = $validator->validate('Strength', 3, null, $certificate);

        $this->assertEquals('CV file is required and must be valid.', $error);
    }

    // ========================================================================
    //  STRATEGY PATTERN — StrictApplicationValidator
    // ========================================================================

    public function test_strict_validator_passes_with_valid_data(): void
    {
        $validator = new StrictApplicationValidator();
        $cv = $this->createUploadedFile('cv.pdf', 1, 'application/pdf');
        $certificate = $this->createUploadedFile('cert.pdf', 1, 'application/pdf');

        $error = $validator->validate('Strength Training', 5, $cv, $certificate);

        $this->assertNull($error);
    }

    public function test_strict_validator_rejects_less_than_2_years_experience(): void
    {
        $validator = new StrictApplicationValidator();
        $cv = $this->createUploadedFile('cv.pdf', 1, 'application/pdf');
        $certificate = $this->createUploadedFile('cert.pdf', 1, 'application/pdf');

        $error = $validator->validate('Yoga', 1, $cv, $certificate);

        $this->assertEquals('Minimum 2 years of experience required for strict validation.', $error);
    }

    public function test_strict_validator_accepts_exactly_2_years(): void
    {
        $validator = new StrictApplicationValidator();
        $cv = $this->createUploadedFile('cv.pdf', 1, 'application/pdf');
        $certificate = $this->createUploadedFile('cert.pdf', 1, 'application/pdf');

        $error = $validator->validate('Yoga', 2, $cv, $certificate);

        $this->assertNull($error);
    }

    public function test_strict_validator_rejects_cv_over_3mb(): void
    {
        $validator = new StrictApplicationValidator();
        $cv = $this->createUploadedFile('cv.pdf', 4 * 1024, 'application/pdf');
        $certificate = $this->createUploadedFile('cert.pdf', 1, 'application/pdf');

        $error = $validator->validate('Strength', 5, $cv, $certificate);

        $this->assertEquals('CV file must be under 3MB.', $error);
    }

    public function test_strict_validator_rejects_certificate_over_3mb(): void
    {
        $validator = new StrictApplicationValidator();
        $cv = $this->createUploadedFile('cv.pdf', 1, 'application/pdf');
        $certificate = $this->createUploadedFile('cert.pdf', 4 * 1024, 'application/pdf');

        $error = $validator->validate('Strength', 5, $cv, $certificate);

        $this->assertEquals('Certificate file must be under 3MB.', $error);
    }

    public function test_strict_validator_rejects_empty_specialization(): void
    {
        $validator = new StrictApplicationValidator();
        $cv = $this->createUploadedFile('cv.pdf', 1, 'application/pdf');
        $certificate = $this->createUploadedFile('cert.pdf', 1, 'application/pdf');

        $error = $validator->validate('', 5, $cv, $certificate);

        $this->assertEquals('Specialization is required.', $error);
    }

    // ========================================================================
    //  FACTORY PATTERN — ApplicationValidatorFactory
    // ========================================================================

    public function test_factory_creates_standard_validator_by_default(): void
    {
        $validator = ApplicationValidatorFactory::create();

        $this->assertInstanceOf(StandardApplicationValidator::class, $validator);
    }

    public function test_factory_creates_standard_validator_explicitly(): void
    {
        $validator = ApplicationValidatorFactory::create('standard');

        $this->assertInstanceOf(StandardApplicationValidator::class, $validator);
    }

    public function test_factory_creates_strict_validator(): void
    {
        $validator = ApplicationValidatorFactory::create('strict');

        $this->assertInstanceOf(StrictApplicationValidator::class, $validator);
    }

    public function test_factory_defaults_to_standard_for_unknown_mode(): void
    {
        $validator = ApplicationValidatorFactory::create('extreme');

        $this->assertInstanceOf(StandardApplicationValidator::class, $validator);
    }

    // ========================================================================
    //  CONTRAST: Same tests but using both strategies to prove
    //  they enforce different rules (Strategy Pattern benefit).
    // ========================================================================

    public function test_standard_allows_1_year_experience_but_strict_rejects(): void
    {
        $standard = new StandardApplicationValidator();
        $strict = new StrictApplicationValidator();
        $cv = $this->createUploadedFile('cv.pdf', 1, 'application/pdf');
        $certificate = $this->createUploadedFile('cert.pdf', 1, 'application/pdf');

        $standardResult = $standard->validate('Yoga', 1, $cv, $certificate);
        $strictResult = $strict->validate('Yoga', 1, $cv, $certificate);

        $this->assertNull($standardResult);
        $this->assertEquals(
            'Minimum 2 years of experience required for strict validation.',
            $strictResult
        );
    }

    public function test_standard_allows_3mb_cv_but_strict_allows_also(): void
    {
        $standard = new StandardApplicationValidator();
        $strict = new StrictApplicationValidator();
        $cv = $this->createUploadedFile('cv.pdf', 3 * 1024, 'application/pdf');
        $certificate = $this->createUploadedFile('cert.pdf', 1, 'application/pdf');

        $this->assertNull($standard->validate('Strength', 5, $cv, $certificate));
        $this->assertNull($strict->validate('Strength', 5, $cv, $certificate));
    }

    public function test_standard_allows_4mb_cv_but_strict_rejects(): void
    {
        $standard = new StandardApplicationValidator();
        $strict = new StrictApplicationValidator();
        $cv = $this->createUploadedFile('cv.pdf', 4 * 1024, 'application/pdf');
        $certificate = $this->createUploadedFile('cert.pdf', 1, 'application/pdf');

        $this->assertNull($standard->validate('Strength', 5, $cv, $certificate));
        $this->assertEquals(
            'CV file must be under 3MB.',
            $strict->validate('Strength', 5, $cv, $certificate)
        );
    }
}

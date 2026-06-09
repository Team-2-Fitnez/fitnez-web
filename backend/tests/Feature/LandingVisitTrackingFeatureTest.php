<?php

namespace Tests\Feature;

use App\Models\LandingPageVisit;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Helpers\WithJwtAuth;
use Tests\TestCase;

class LandingVisitTrackingFeatureTest extends TestCase
{
    use RefreshDatabase;
    use WithJwtAuth;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_repeated_page_view_updates_existing_daily_visitor_row(): void
    {
        Carbon::setTestNow('2026-06-08 10:00:00');

        $payload = $this->landingVisitPayload();

        $this->postJson('/api/analytics/landing-visit', $payload)->assertStatus(201);
        $this->postJson('/api/analytics/landing-visit', $payload)->assertStatus(201);

        $this->assertSame(1, LandingPageVisit::query()->count());

        $visit = LandingPageVisit::query()->firstOrFail();

        $this->assertSame('VIS-TEST01', $visit->visitor_uuid);
        $this->assertSame(2, $visit->page_view_count);
    }

    public function test_heartbeat_updates_last_seen_without_incrementing_page_views(): void
    {
        Carbon::setTestNow('2026-06-08 10:00:00');

        $payload = $this->landingVisitPayload();

        $this->postJson('/api/analytics/landing-visit', $payload)->assertStatus(201);

        $initialVisit = LandingPageVisit::query()->firstOrFail();
        $initialLastSeen = $initialVisit->last_seen_at;

        Carbon::setTestNow('2026-06-08 10:05:00');

        $this->postJson('/api/analytics/landing-visit/heartbeat', $payload)->assertStatus(200);

        $visit = $initialVisit->fresh();

        $this->assertSame(1, LandingPageVisit::query()->count());
        $this->assertSame(1, $visit->page_view_count);
        $this->assertTrue($visit->last_seen_at->greaterThan($initialLastSeen));
    }

    public function test_unique_visitors_today_stays_one_for_same_visitor_refreshes(): void
    {
        Carbon::setTestNow('2026-06-08 10:00:00');

        Role::factory()->admin()->create();
        $admin = User::factory()->admin()->create();
        $payload = $this->landingVisitPayload();

        $this->postJson('/api/analytics/landing-visit', $payload)->assertStatus(201);
        $this->postJson('/api/analytics/landing-visit', $payload)->assertStatus(201);
        $this->postJson('/api/analytics/landing-visit/heartbeat', $payload)->assertStatus(200);

        $this->authenticateAs($admin);

        $response = $this->getJson('/api/admin/landing-visits/summary');

        $response->assertStatus(200)
            ->assertJsonPath('data.unique_visitors_today', 1)
            ->assertJsonPath('data.total_page_views', 2);
    }

    private function landingVisitPayload(): array
    {
        return [
            'visitor_uuid' => 'VIS-TEST01',
            'session_uuid' => 'SES-TEST01',
            'browser_context' => 'private_mode_not_detected',
            'browser_context_label' => 'Private Mode Not Detected',
            'client_browser_name' => 'Chrome',
            'client_browser_engine' => 'Chromium',
            'private_mode_detected' => false,
            'private_mode_confidence' => 'low',
            'private_mode_source' => 'test',
            'referrer' => '',
            'landing_url' => 'https://fitnez.test/',
            'route_path' => '/',
            'query_params' => [],
            'locale' => 'id-ID',
            'timezone' => 'Asia/Jakarta',
            'screen_width' => 1440,
            'screen_height' => 900,
            'viewport_width' => 1280,
            'viewport_height' => 720,
        ];
    }
}

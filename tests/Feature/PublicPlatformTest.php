<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPlatformTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_identifies_the_platform(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Investment Opportunities Mapping Project')
            ->assertSee('manifest.webmanifest');
    }

    public function test_health_endpoint_reports_service_availability(): void
    {
        $this->getJson(route('health'))
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('service', 'iomp-web');
    }

    public function test_pwa_public_assets_exist(): void
    {
        $this->assertFileExists(public_path('manifest.webmanifest'));
        $this->assertFileExists(public_path('sw.js'));
        $this->assertFileExists(public_path('offline.html'));
        $this->assertFileExists(public_path('icons/iomp-icon.svg'));
    }
}

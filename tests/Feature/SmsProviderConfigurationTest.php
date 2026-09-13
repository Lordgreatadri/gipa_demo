<?php

namespace Tests\Feature;

use App\Models\SmsProviderConfig;
use App\Models\User;
use App\Support\SmsPermissions;
use Database\Seeders\WorkflowPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SmsProviderConfigurationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(WorkflowPermissionSeeder::class);
    }

    private function manager(): User
    {
        $user = User::factory()->create(['account_type' => User::ACCOUNT_STAFF]);
        $user->givePermissionTo(SmsPermissions::ALL);

        return $user;
    }

    public function test_staff_without_permission_cannot_view_sms_configuration(): void
    {
        $staff = User::factory()->create(['account_type' => User::ACCOUNT_STAFF]);

        $this->actingAs($staff)->get(route('staff.sms.index'))->assertForbidden();
    }

    public function test_manager_sees_every_catalogued_provider(): void
    {
        $this->actingAs($this->manager())
            ->get(route('staff.sms.index'))
            ->assertOk()
            ->assertSee('Wigal Frog')
            ->assertSee('Twilio');
    }

    public function test_credentials_are_stored_encrypted_and_never_rendered(): void
    {
        $this->actingAs($this->manager())
            ->put(route('staff.sms.update', 'frog'), [
                'label' => 'Wigal Frog production',
                'sender_id' => 'GIPA',
                'priority' => 10,
                'is_active' => '1',
                'credentials' => [
                    'username' => 'gipa-ops',
                    'api_key' => 'super-secret-key',
                    'base_url' => '',
                ],
            ])
            ->assertRedirect(route('staff.sms.index'));

        $config = SmsProviderConfig::where('provider', 'frog')->firstOrFail();

        $this->assertTrue($config->is_active);
        $this->assertSame('super-secret-key', $config->credential('api_key'));
        $this->assertSame('https://frogapi.wigal.com.gh/api/v3', $config->credential('base_url'));
        $this->assertNotEmpty($config->webhook_secret);

        $raw = DB::table('sms_provider_configs')->where('id', $config->id)->first();
        $this->assertStringNotContainsString('super-secret-key', (string) $raw->credentials);

        $this->actingAs($this->manager())
            ->get(route('staff.sms.edit', 'frog'))
            ->assertOk()
            ->assertDontSee('super-secret-key');
    }

    public function test_provider_cannot_go_active_without_required_credentials(): void
    {
        $this->actingAs($this->manager())
            ->put(route('staff.sms.update', 'twilio'), [
                'priority' => 20,
                'is_active' => '1',
                'credentials' => ['account_sid' => 'AC123', 'auth_token' => '', 'base_url' => ''],
            ])
            ->assertRedirect(route('staff.sms.index'));

        $this->assertFalse(SmsProviderConfig::where('provider', 'twilio')->firstOrFail()->is_active);
    }

    public function test_secret_credentials_are_retained_when_the_field_is_left_blank(): void
    {
        $manager = $this->manager();

        $this->actingAs($manager)->put(route('staff.sms.update', 'frog'), [
            'priority' => 10,
            'credentials' => ['username' => 'gipa-ops', 'api_key' => 'keep-me', 'base_url' => ''],
        ]);

        $this->actingAs($manager)->put(route('staff.sms.update', 'frog'), [
            'priority' => 15,
            'credentials' => ['username' => 'gipa-ops', 'api_key' => '', 'base_url' => ''],
        ]);

        $config = SmsProviderConfig::where('provider', 'frog')->firstOrFail();
        $this->assertSame('keep-me', $config->credential('api_key'));
        $this->assertSame(15, $config->priority);
    }

    public function test_manager_can_toggle_a_configured_provider(): void
    {
        $manager = $this->manager();

        $this->actingAs($manager)->put(route('staff.sms.update', 'frog'), [
            'priority' => 10,
            'credentials' => ['username' => 'gipa-ops', 'api_key' => 'key', 'base_url' => ''],
        ]);

        $config = SmsProviderConfig::where('provider', 'frog')->firstOrFail();

        $this->actingAs($manager)->post(route('staff.sms.toggle', $config))->assertRedirect();
        $this->assertTrue($config->fresh()->is_active);

        $this->actingAs($manager)->post(route('staff.sms.toggle', $config))->assertRedirect();
        $this->assertFalse($config->fresh()->is_active);
    }

    public function test_balance_lookup_caches_the_provider_snapshot(): void
    {
        Http::fake([
            '*/balance' => Http::response([
                'status' => 'SUCCESS',
                'message' => 'SUCCESS',
                'data' => ['cashbalance' => 677.56, 'bundles' => ['SMS' => 82]],
            ]),
        ]);

        $config = SmsProviderConfig::create([
            'provider' => 'frog',
            'is_active' => true,
            'credentials' => ['username' => 'gipa-ops', 'api_key' => 'key'],
        ]);

        $this->actingAs($this->manager())
            ->post(route('staff.sms.balance', $config))
            ->assertRedirect();

        $config->refresh();
        $this->assertSame(677.56, $config->balance_snapshot['credit_balance']);
        $this->assertSame(82, $config->balance_snapshot['sms_units']);
        $this->assertNotNull($config->balance_checked_at);
    }
}

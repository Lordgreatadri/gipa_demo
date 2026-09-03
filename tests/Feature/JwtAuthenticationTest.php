<?php

namespace Tests\Feature;

use App\Models\InvestorProfile;
use App\Models\Region;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class JwtAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_access_and_refresh_tokens_rotate_and_revoke(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.'.random_int(1, 254)]);
        $user = User::factory()->create([
            'email' => 'investor@example.test',
            'password' => Hash::make('Secure-password-123'),
            'status' => User::STATUS_ACTIVE,
        ]);
        $profile = InvestorProfile::create(['user_id' => $user->id, 'display_name' => 'Private Investor']);
        $sector = Sector::create(['code' => 'ICT', 'name' => 'Technology']);
        $region = Region::create(['code' => 'GA', 'name' => 'Greater Accra']);

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => 'Investor@Example.test',
            'password' => 'Secure-password-123',
        ])->assertOk()->assertJsonStructure(['token_type', 'access_token', 'expires_in', 'refresh_token', 'refresh_expires_at']);

        $firstAccess = $login->json('access_token');
        $firstRefresh = $login->json('refresh_token');
        $this->withToken($firstAccess)->getJson('/api/v1/investor/me')
            ->assertOk()
            ->assertJsonPath('data.uuid', $profile->uuid)
            ->assertJsonMissingPath('data.documents');
        $this->withToken($firstAccess)->putJson('/api/v1/investor/preferences', [
            'sector_uuids' => [$sector->uuid],
            'region_uuids' => [$region->uuid],
            'minimum_investment' => 100000,
            'maximum_investment' => 1000000,
            'currency' => 'GHS',
            'minimum_readiness_score' => 60,
        ])->assertOk()
            ->assertJsonPath('data.sectors.0.uuid', $sector->uuid)
            ->assertJsonPath('data.regions.0.uuid', $region->uuid);
        $this->withToken($firstAccess)->postJson('/api/v1/auth/logout')->assertOk();
        $this->withToken($firstAccess)->postJson('/api/v1/auth/logout')->assertUnauthorized();
        $this->postJson('/api/v1/auth/refresh', ['refresh_token' => $firstRefresh])->assertUnprocessable();

        $secondLogin = $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'Secure-password-123'])->assertOk();
        $secondRefresh = $secondLogin->json('refresh_token');
        $rotated = $this->postJson('/api/v1/auth/refresh', ['refresh_token' => $secondRefresh])->assertOk();

        $this->assertNotSame($secondRefresh, $rotated->json('refresh_token'));
        $this->postJson('/api/v1/auth/refresh', ['refresh_token' => $secondRefresh])->assertUnprocessable();
    }

    public function test_inactive_accounts_cannot_log_in(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.'.random_int(1, 254)]);
        $user = User::factory()->create(['status' => User::STATUS_SUSPENDED, 'password' => Hash::make('Secure-password-123')]);

        $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'Secure-password-123'])
            ->assertUnprocessable();
    }

    public function test_password_reset_revokes_all_api_sessions(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.'.random_int(1, 254)]);
        $user = User::factory()->create([
            'email' => 'recovery@example.test',
            'password' => Hash::make('Compromised-password-123'),
        ]);

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'Compromised-password-123',
        ])->assertOk();

        $this->post(route('password.update'), [
            'token' => Password::broker()->createToken($user),
            'email' => $user->email,
            'password' => 'Recovered-password-456',
            'password_confirmation' => 'Recovered-password-456',
        ])->assertRedirect(route('login'));

        $this->postJson('/api/v1/auth/refresh', [
            'refresh_token' => $login->json('refresh_token'),
        ])->assertUnprocessable();

        $this->assertDatabaseMissing('api_token_sessions', [
            'user_id' => $user->id,
            'revoked_at' => null,
        ]);
    }
}

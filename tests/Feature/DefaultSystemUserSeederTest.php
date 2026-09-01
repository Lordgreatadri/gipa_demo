<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DefaultSystemUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DefaultSystemUserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_one_verified_super_administrator_from_configuration(): void
    {
        config()->set('iomp.default_system_user', [
            'name' => 'Configured Administrator',
            'email' => 'configured-admin@example.test',
            'password' => 'EnvironmentOnly!Password42',
        ]);

        $this->seed(DefaultSystemUserSeeder::class);

        $createdUser = User::query()->sole();
        $createdUser->update(['password' => 'UserChanged!Password84']);

        $this->seed(DefaultSystemUserSeeder::class);

        $user = User::query()->sole();

        $this->assertSame('Configured Administrator', $user->name);
        $this->assertSame(User::ACCOUNT_STAFF, $user->account_type);
        $this->assertSame(User::STATUS_ACTIVE, $user->status);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(Hash::check('UserChanged!Password84', $user->password));
        $this->assertTrue($user->hasRole('Super Administrator'));
    }

    public function test_it_refuses_to_seed_when_environment_credentials_are_missing(): void
    {
        config()->set('iomp.default_system_user', [
            'name' => null,
            'email' => null,
            'password' => null,
        ]);

        $this->expectException(ValidationException::class);

        $this->seed(DefaultSystemUserSeeder::class);
    }
}

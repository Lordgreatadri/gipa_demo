<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DefaultRoleUserSeeder;
use Database\Seeders\WorkflowPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DefaultRoleUserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_one_default_account_for_every_role_idempotently(): void
    {
        $this->configureDemoUsers();
        $this->seed([WorkflowPermissionSeeder::class, DefaultRoleUserSeeder::class]);

        $staff = User::query()->where('email', 'demo.super-admin@example.test')->sole();
        $staff->update(['password' => 'UserChanged!Password84']);

        $this->seed(DefaultRoleUserSeeder::class);

        $accounts = [
            'Super Administrator' => 'demo.super-admin@example.test',
            'Content / Data Manager' => 'demo.content-manager@example.test',
            'District Officer' => 'demo.district-officer@example.test',
            'Field Agent' => 'demo.field-agent@example.test',
            'Reviewer / Approver' => 'demo.reviewer@example.test',
            'Investor' => 'demo.investor01@example.test',
        ];

        $this->assertSame(6, User::query()->count());
        $this->assertSame(5, User::query()->where('account_type', User::ACCOUNT_STAFF)->count());
        $this->assertSame(1, User::query()->where('account_type', User::ACCOUNT_INVESTOR)->count());
        $this->assertTrue(Hash::check('UserChanged!Password84', $staff->fresh()->password));

        foreach ($accounts as $role => $email) {
            $this->assertTrue(User::query()->where('email', $email)->sole()->hasRole($role));
        }
    }

    public function test_it_refuses_to_seed_without_environment_credentials(): void
    {
        config()->set('iomp.demo_users', []);

        $this->expectException(ValidationException::class);

        $this->seed(DefaultRoleUserSeeder::class);
    }

    private function configureDemoUsers(): void
    {
        config()->set('iomp.demo_users', [
            'staff_password' => 'TestStaffPassword123!',
            'investor_password' => 'TestInvestorPassword123!',
            'investor_email_pattern' => 'demo.investor%02d@example.test',
            'investor_count' => 150,
            'roles' => [
                ['role' => 'Super Administrator', 'name' => 'Demo Super Administrator', 'email' => 'demo.super-admin@example.test', 'type' => 'staff'],
                ['role' => 'Content / Data Manager', 'name' => 'Demo Content Manager', 'email' => 'demo.content-manager@example.test', 'type' => 'staff'],
                ['role' => 'District Officer', 'name' => 'Demo District Officer', 'email' => 'demo.district-officer@example.test', 'type' => 'staff'],
                ['role' => 'Field Agent', 'name' => 'Demo Field Agent', 'email' => 'demo.field-agent@example.test', 'type' => 'staff'],
                ['role' => 'Reviewer / Approver', 'name' => 'Demo Reviewer', 'email' => 'demo.reviewer@example.test', 'type' => 'staff'],
                ['role' => 'Investor', 'name' => 'Ama Mensah', 'email' => 'demo.investor01@example.test', 'type' => 'investor'],
            ],
        ]);
    }
}

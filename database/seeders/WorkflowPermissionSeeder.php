<?php

namespace Database\Seeders;

use App\Support\WorkflowPermissions;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class WorkflowPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (WorkflowPermissions::ALL as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        Role::findOrCreate('Super Administrator', 'web')->syncPermissions(WorkflowPermissions::ALL);
        Role::findOrCreate('Content / Data Manager', 'web')->syncPermissions([
            WorkflowPermissions::DISTRICT_SUBMIT,
            WorkflowPermissions::OPPORTUNITY_SUBMIT,
            WorkflowPermissions::OPPORTUNITY_LIFECYCLE,
        ]);
        Role::findOrCreate('District Officer', 'web')->syncPermissions([
            WorkflowPermissions::DISTRICT_SUBMIT,
            WorkflowPermissions::OPPORTUNITY_SUBMIT,
        ]);
        Role::findOrCreate('Field Agent', 'web')->syncPermissions([
            WorkflowPermissions::DISTRICT_SUBMIT,
            WorkflowPermissions::OPPORTUNITY_SUBMIT,
        ]);
        Role::findOrCreate('Reviewer / Approver', 'web')->syncPermissions([
            WorkflowPermissions::DISTRICT_REVIEW,
            WorkflowPermissions::DISTRICT_REASSIGN,
            WorkflowPermissions::OPPORTUNITY_REVIEW,
            WorkflowPermissions::OPPORTUNITY_REASSIGN,
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}

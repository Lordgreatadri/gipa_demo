<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class DefaultSystemUserSeeder extends Seeder
{
    public function run(): void
    {
        $credentials = config('iomp.default_system_user');

        $validator = Validator::make($credentials, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'password' => ['required', 'string', Password::min(16)->mixedCase()->letters()->numbers()->symbols()],
        ], [
            'required' => 'The :attribute must be configured in the environment before running the default system user seeder.',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $credentials = $validator->validated();
        $user = User::query()->firstOrNew(['email' => $credentials['email']]);

        if (! $user->exists) {
            $user->password = $credentials['password'];
        }

        $user->forceFill([
            'name' => $credentials['name'],
            'account_type' => User::ACCOUNT_STAFF,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        $role = Role::findOrCreate('Super Administrator', 'web');
        $user->syncRoles([$role]);
    }
}

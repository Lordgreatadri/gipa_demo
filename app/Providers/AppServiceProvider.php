<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(fn (User $user) => $user->hasRole('Super Administrator') ? true : null);

        ResetPassword::createUrlUsing(function (User $user, string $token): string {
            return route('password.reset', array_filter([
                'token' => $token,
                'email' => $user->getEmailForPasswordReset(),
                'portal' => $user->isStaff() ? 'staff' : null,
            ]));
        });
    }
}

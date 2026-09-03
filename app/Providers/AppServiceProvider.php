<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

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
        RateLimiter::for('api-login', fn (Request $request) => Limit::perMinute(6)->by(Str::lower((string) $request->input('email')).'|'.$request->ip()));
        RateLimiter::for('api-refresh', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));
        RateLimiter::for('api-protected', fn (Request $request) => Limit::perMinute(60)->by($request->user()?->uuid ?? $request->ip()));
        RateLimiter::for('api-public', fn (Request $request) => Limit::perMinute(120)->by($request->ip()));
        RateLimiter::for('api-certificate-verification', fn (Request $request) => Limit::perMinute(30)->by($request->ip()));
        RateLimiter::for('api-inquiry', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));

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

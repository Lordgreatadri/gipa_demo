<?php

namespace App\Providers;

use App\Notifications\Channels\SmsChannel;
use App\Services\Sms\SmsGateway;
use App\Services\Sms\SmsProviderRegistry;
use App\Services\Sms\Support\CircuitBreaker;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class SmsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SmsProviderRegistry::class);
        $this->app->singleton(CircuitBreaker::class);
        $this->app->singleton(SmsGateway::class);
    }

    public function boot(): void
    {
        Notification::extend('sms', fn ($app) => $app->make(SmsChannel::class));

        // Provider callback endpoints. Signature validation happens inside the
        // configured validator for each route, so no auth middleware is applied.
        Route::middleware(['api', 'throttle:sms-webhooks'])
            ->group(function (): void {
                Route::webhooks('webhooks/sms/frog', 'frog-sms');
                Route::webhooks('webhooks/sms/twilio', 'twilio-sms');
            });
    }
}

<?php

namespace App\Providers;

use App\Console\Commands\ExpirePayments;
use App\Services\PaymentService;
use App\Services\WebhookEmitter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(WebhookEmitter::class);
        $this->app->singleton(PaymentService::class, fn ($app) =>
            new PaymentService($app->make(WebhookEmitter::class))
        );
    }

    public function boot(): void
    {
        //
    }
}

<?php

namespace App\Providers;

use App\Services\ApiClient;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ApiClient::class, function () {
            return new ApiClient(
                config('services.api.base_url'),
                config('services.api.verify_ssl'),
            );
        });
    }

    public function boot(): void
    {
        //
    }
}

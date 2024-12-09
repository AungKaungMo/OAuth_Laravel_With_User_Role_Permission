<?php

namespace App\Providers;
use App\Services\MailService;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MailService::class, function ($app) {
            return new MailService();
        });
    }


    public function boot(): void
    {
        //
    }
}

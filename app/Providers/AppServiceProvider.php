<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

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
        Passport::tokensCan([
            'resources:read' => 'Read imported resources.',
            'resources:write' => 'Create, update, and delete imported resources.',
        ]);

        Passport::setDefaultScope([
            'resources:read',
            'resources:write',
        ]);
    }
}

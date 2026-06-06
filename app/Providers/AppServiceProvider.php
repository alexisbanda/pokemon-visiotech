<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Combat\Random\RandomFactor;
use App\Domain\Combat\Random\StandardRandomFactor;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // En producción el factor aleatorio (85-100) es real; en tests se
        // sustituye por FixedRandomFactor para combates deterministas.
        $this->app->bind(RandomFactor::class, StandardRandomFactor::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

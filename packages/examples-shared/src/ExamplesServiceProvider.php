<?php

declare(strict_types=1);

namespace Rankbeam\Examples;

use Illuminate\Support\ServiceProvider;

/**
 * Auto-discovered. Loads the shared posts/pages migrations so every reference
 * app gets the same content tables without copying migration files around.
 */
final class ExamplesServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}

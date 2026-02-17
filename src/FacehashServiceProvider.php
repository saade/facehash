<?php

namespace Saade\Facehash;

use Illuminate\Support\ServiceProvider;

class FacehashServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/facehash.php', 'facehash');

        $this->app->singleton(Facehash::class, fn () => new Facehash);
    }

    public function boot(): void
    {
        if ($this->app['config']->get('facehash.route.enabled', false)) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        }

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/facehash.php' => config_path('facehash.php'),
            ], 'facehash-config');
        }
    }
}

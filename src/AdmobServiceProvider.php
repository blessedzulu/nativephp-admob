<?php

declare(strict_types=1);

namespace BlessedZulu\NativePhpAdmob;

use Illuminate\Support\ServiceProvider;

class AdmobServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/admob.php', 'admob');

        $this->app->singleton('admob', fn () => new Admob);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/admob.php' => config_path('admob.php'),
        ], 'admob-config');
    }
}

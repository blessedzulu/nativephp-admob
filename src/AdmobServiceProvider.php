<?php

declare(strict_types=1);

namespace BlessedZulu\NativePhpAdmob;

use BlessedZulu\NativePhpAdmob\Contracts\Bridge;
use BlessedZulu\NativePhpAdmob\Events\ConsentChanged;
use BlessedZulu\NativePhpAdmob\Support\NativeBridge;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AdmobServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/admob.php', 'admob');

        $this->app->singleton(Bridge::class, fn () => new NativeBridge);

        $this->app->singleton('admob', fn ($app) => new Admob(
            $app->make(Bridge::class),
            (array) $app['config']->get('admob', []),
        ));
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/admob.php' => config_path('admob.php'),
        ], 'admob-config');

        Event::listen(ConsentChanged::class, function (ConsentChanged $event) {
            $this->app->make('admob')->onConsentChanged($event->status);
        });
    }
}

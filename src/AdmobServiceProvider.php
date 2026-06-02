<?php

declare(strict_types=1);

namespace BlessedZulu\NativePhpAdmob;

use BlessedZulu\NativePhpAdmob\Commands\SubstituteManifestPlaceholdersCommand;
use BlessedZulu\NativePhpAdmob\Contracts\Bridge;
use BlessedZulu\NativePhpAdmob\Events\ConsentChanged;
use BlessedZulu\NativePhpAdmob\Http\Controllers\AdmobCallController;
use BlessedZulu\NativePhpAdmob\Http\Controllers\AdmobTestController;
use BlessedZulu\NativePhpAdmob\Support\LoggingBridge;
use BlessedZulu\NativePhpAdmob\Support\NativeBridge;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AdmobServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/admob.php', 'admob');

        $this->app->singleton(Bridge::class, function ($app) {
            $bridge = new NativeBridge;

            if ($app['config']->get('admob.debug', false)) {
                return new LoggingBridge($bridge, $app['log']);
            }

            return $bridge;
        });

        $this->app->singleton('admob', function ($app) {
            $config = (array) $app['config']->get('admob', []);

            return new Admob(
                $app->make(Bridge::class),
                $config,
                $app['cache']->store($config['frequency_store'] ?? null),
            );
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/admob.php' => config_path('admob.php'),
        ], 'admob-config');

        /*
         * Registers the `admob::` view namespace so <x-admob::banner /> resolves
         * to resources/views/components/banner.blade.php (anonymous component).
         */
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'admob');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/admob'),
        ], 'admob-views');

        // Ship the JS module + types so JS apps can pull them into their build.
        $this->publishes([
            __DIR__.'/../resources/js' => resource_path('js/vendor/admob'),
        ], 'admob-js');

        // Thin same-origin endpoint backing the JS API. Runs the Admob facade,
        // so slot resolution + consent + caps + the enabled kill-switch apply.
        // No CSRF/session middleware - it's a localhost native-WebView endpoint,
        // mirroring NativePHP's own /_native/api/call (which is likewise exempt).
        if ($this->app['config']->get('admob.js_api', true)) {
            Route::prefix(ltrim((string) $this->app['config']->get('admob.js_api_prefix', '_admob'), '/'))
                ->post('call', AdmobCallController::class);
        }

        /*
         * Built-in self-contained test/debug page. Bare route (no CSRF/session),
         * gated on config('admob.test_page') - default on outside production.
         */
        if ($this->app['config']->get('admob.test_page', false)) {
            Route::get(
                ltrim((string) $this->app['config']->get('admob.test_route', '_admob/test'), '/'),
                AdmobTestController::class,
            );
        }

        Event::listen(ConsentChanged::class, function (ConsentChanged $event) {
            $this->app->make('admob')->onConsentChanged($event->status);
        });

        if ($this->app->runningInConsole()) {
            $this->commands([
                SubstituteManifestPlaceholdersCommand::class,
            ]);
        }
    }
}

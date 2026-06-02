<?php

declare(strict_types=1);

namespace BlessedZulu\NativePhpAdmob\Tests;

use BlessedZulu\NativePhpAdmob\AdmobServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [AdmobServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // The JS API route runs in the `web` group (session + encryption), which
        // needs an app key under Testbench.
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
    }
}

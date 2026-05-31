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
}

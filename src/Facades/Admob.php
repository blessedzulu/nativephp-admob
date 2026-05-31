<?php

declare(strict_types=1);

namespace BlessedZulu\NativePhpAdmob\Facades;

use Illuminate\Support\Facades\Facade;

class Admob extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'admob';
    }
}

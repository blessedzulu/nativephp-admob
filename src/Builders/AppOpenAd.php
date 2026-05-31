<?php

declare(strict_types=1);

namespace BlessedZulu\NativePhpAdmob\Builders;

use BlessedZulu\NativePhpAdmob\Admob;
use BlessedZulu\NativePhpAdmob\Contracts\Bridge;

/**
 * App-open ads have no explicit show() method. The native side observes the
 * app's lifecycle and shows the cached ad when the app returns to the
 * foreground. PHP's responsibility is just to load() and let the native
 * lifecycle observer handle the rest.
 */
class AppOpenAd
{
    public const FORMAT = 'app_open';

    public function __construct(
        protected Bridge $bridge,
        protected Admob $manager,
        protected string $slot,
        protected string $adUnitId,
    ) {}

    public function load(): self
    {
        $this->bridge->call('Admob.LoadAppOpen', [
            'slot' => $this->slot,
            'format' => self::FORMAT,
            'unit_id' => $this->adUnitId,
        ]);

        return $this;
    }
}

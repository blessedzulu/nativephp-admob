<?php

declare(strict_types=1);

namespace BlessedZulu\NativePhpAdmob\Consent;

use BlessedZulu\NativePhpAdmob\Contracts\Bridge;

class Att
{
    public const STATUS_AUTHORIZED = 'authorized';

    public const STATUS_DENIED = 'denied';

    public const STATUS_RESTRICTED = 'restricted';

    public const STATUS_NOT_DETERMINED = 'notDetermined';

    public const STATUS_UNSUPPORTED = 'unsupported';

    public function __construct(protected Bridge $bridge) {}

    public function requestAuthorization(): void
    {
        if (! $this->isSupported()) {
            return;
        }

        $this->bridge->call('Admob.AttRequest');
    }

    public function status(): string
    {
        if (! $this->isSupported()) {
            return self::STATUS_UNSUPPORTED;
        }

        $response = $this->bridge->call('Admob.AttStatus');

        return (string) ($response['data']['status'] ?? self::STATUS_NOT_DETERMINED);
    }

    protected function isSupported(): bool
    {
        $response = $this->bridge->call('Admob.Platform');

        return (string) ($response['data']['platform'] ?? '') === 'ios';
    }
}

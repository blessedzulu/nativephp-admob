<?php

declare(strict_types=1);

namespace BlessedZulu\NativePhpAdmob\Consent;

use BlessedZulu\NativePhpAdmob\Contracts\Bridge;
use BlessedZulu\NativePhpAdmob\Events\ConsentChanged;

class Ump
{
    public function __construct(protected Bridge $bridge) {}

    public function requestConsentInfo(): void
    {
        $this->bridge->call('Admob.UmpRequestInfo');
    }

    public function showFormIfRequired(): void
    {
        $this->bridge->call('Admob.UmpShowForm');
    }

    public function canRequestAds(): bool
    {
        $response = $this->bridge->call('Admob.UmpCanRequestAds');

        return (bool) ($response['data']['can_request'] ?? false);
    }

    public function status(): string
    {
        $response = $this->bridge->call('Admob.UmpStatus');

        return (string) ($response['data']['status'] ?? ConsentChanged::STATUS_UNKNOWN);
    }

    public function reset(): void
    {
        $this->bridge->call('Admob.UmpReset');
    }
}

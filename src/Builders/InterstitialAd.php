<?php

declare(strict_types=1);

namespace BlessedZulu\NativePhpAdmob\Builders;

use BlessedZulu\NativePhpAdmob\Admob;
use BlessedZulu\NativePhpAdmob\Contracts\Bridge;
use Illuminate\Support\Facades\Log;

class InterstitialAd
{
    public const FORMAT = 'interstitial';

    public function __construct(
        protected Bridge $bridge,
        protected Admob $manager,
        protected string $slot,
        protected string $adUnitId,
    ) {}

    public function load(): self
    {
        $this->bridge->call('Admob.LoadInterstitial', $this->params());

        return $this;
    }

    public function isReady(): bool
    {
        $response = $this->bridge->call('Admob.InterstitialReady', $this->params());

        return (bool) ($response['data']['ready'] ?? false);
    }

    public function show(): self
    {
        if (! $this->manager->canRequestAds()) {
            Log::warning('Admob: interstitial show() skipped, consent not granted.', ['slot' => $this->slot]);

            return $this;
        }

        $this->bridge->call('Admob.ShowInterstitial', $this->params());

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    protected function params(): array
    {
        return [
            'slot' => $this->slot,
            'format' => self::FORMAT,
            'unit_id' => $this->adUnitId,
        ];
    }
}

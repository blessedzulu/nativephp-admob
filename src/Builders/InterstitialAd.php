<?php

declare(strict_types=1);

namespace BlessedZulu\NativePhpAdmob\Builders;

use Illuminate\Support\Facades\Log;

class InterstitialAd extends AdBuilder
{
    public const FORMAT = 'interstitial';

    protected function format(): string
    {
        return self::FORMAT;
    }

    public function load(): self
    {
        $this->dispatch('Admob.LoadInterstitial');

        return $this;
    }

    public function isReady(): bool
    {
        $response = $this->dispatch('Admob.InterstitialReady');

        return (bool) ($response['data']['ready'] ?? false);
    }

    public function show(): self
    {
        if (! $this->manager->canRequestAds()) {
            Log::warning('Admob: interstitial show() skipped, consent not granted.', ['slot' => $this->slot]);

            return $this;
        }

        if (! $this->passesFrequencyCap()) {
            return $this;
        }

        $this->dispatch('Admob.ShowInterstitial');
        $this->recordShow();

        return $this;
    }
}

<?php

declare(strict_types=1);

namespace BlessedZulu\NativePhpAdmob\Builders;

use Illuminate\Support\Facades\Log;

/**
 * App-open ads. The recommended path is to call load() on app start and let
 * the native lifecycle observer (AppOpenLifecycle) auto-show the cached ad
 * on app foreground (after the first resume, with a 4-hour staleness check).
 *
 * isReady() and show() are exposed for manual override when the consumer
 * wants explicit control over timing (e.g. on a specific in-app event rather
 * than on every foreground).
 */
class AppOpenAd extends AdBuilder
{
    public const FORMAT = 'app_open';

    protected function format(): string
    {
        return self::FORMAT;
    }

    public function load(): self
    {
        $this->dispatch('Admob.LoadAppOpen');

        return $this;
    }

    public function isReady(): bool
    {
        $response = $this->dispatch('Admob.AppOpenReady');

        return (bool) ($response['data']['ready'] ?? false);
    }

    public function show(): self
    {
        if (! $this->manager->canRequestAds()) {
            Log::warning('Admob: app open show() skipped, consent not granted.', ['slot' => $this->slot]);

            return $this;
        }

        if (! $this->passesFrequencyCap()) {
            return $this;
        }

        $this->dispatch('Admob.ShowAppOpen');
        $this->recordShow();

        return $this;
    }
}

<?php

declare(strict_types=1);

namespace BlessedZulu\NativePhpAdmob\Builders;

use BlessedZulu\NativePhpAdmob\Admob;
use BlessedZulu\NativePhpAdmob\Contracts\Bridge;
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
        $this->bridge->call('Admob.LoadAppOpen', $this->params());

        return $this;
    }

    public function isReady(): bool
    {
        $response = $this->bridge->call('Admob.AppOpenReady', $this->params());

        return (bool) ($response['data']['ready'] ?? false);
    }

    public function show(): self
    {
        if (! $this->manager->canRequestAds()) {
            Log::warning('Admob: app open show() skipped, consent not granted.', ['slot' => $this->slot]);

            return $this;
        }

        $this->bridge->call('Admob.ShowAppOpen', $this->params());

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

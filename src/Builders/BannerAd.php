<?php

declare(strict_types=1);

namespace BlessedZulu\NativePhpAdmob\Builders;

use BlessedZulu\NativePhpAdmob\Admob;
use BlessedZulu\NativePhpAdmob\Contracts\Bridge;
use Illuminate\Support\Facades\Log;

class BannerAd
{
    public const FORMAT = 'banner';

    public function __construct(
        protected Bridge $bridge,
        protected Admob $manager,
        protected string $slot,
        protected string $adUnitId,
    ) {}

    public function load(): self
    {
        $this->bridge->call('Admob.LoadBanner', $this->params());

        return $this;
    }

    public function show(string $position = 'bottom'): self
    {
        if (! $this->manager->canRequestAds()) {
            Log::warning('Admob: banner show() skipped, consent not granted.', ['slot' => $this->slot]);

            return $this;
        }

        $this->bridge->call('Admob.ShowBanner', $this->params(['position' => $position]));

        return $this;
    }

    public function hide(): self
    {
        $this->bridge->call('Admob.HideBanner', $this->params());

        return $this;
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    protected function params(array $extra = []): array
    {
        return array_merge([
            'slot' => $this->slot,
            'format' => self::FORMAT,
            'unit_id' => $this->adUnitId,
        ], $extra);
    }
}

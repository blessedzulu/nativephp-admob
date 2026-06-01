<?php

declare(strict_types=1);

namespace BlessedZulu\NativePhpAdmob\Builders;

use Illuminate\Support\Facades\Log;

class BannerAd extends AdBuilder
{
    public const FORMAT = 'banner';

    protected function format(): string
    {
        return self::FORMAT;
    }

    public function load(): self
    {
        $this->dispatch('Admob.LoadBanner');

        return $this;
    }

    public function show(string $position = 'bottom'): self
    {
        if (! $this->manager->canRequestAds()) {
            Log::warning('Admob: banner show() skipped, consent not granted.', ['slot' => $this->slot]);

            return $this;
        }

        $this->dispatch('Admob.ShowBanner', ['position' => $position]);

        return $this;
    }

    public function hide(): self
    {
        $this->dispatch('Admob.HideBanner');

        return $this;
    }
}

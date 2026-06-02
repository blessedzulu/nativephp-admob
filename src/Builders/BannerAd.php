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

    /**
     * @param  string  $position  'bottom' (default) | 'top'
     * @param  int|null  $offset  extra gap (dp) from the screen edge to clear
     *                            chrome like a native bottom-nav. Null uses
     *                            config('admob.banner.offset.{position}').
     */
    public function show(string $position = 'bottom', ?int $offset = null): self
    {
        if (! $this->manager->canRequestAds()) {
            Log::warning('Admob: banner show() skipped, consent not granted.', ['slot' => $this->slot]);

            return $this;
        }

        $offset ??= (int) config("admob.banner.offset.{$position}", 0);

        $this->dispatch('Admob.ShowBanner', ['position' => $position, 'offset' => $offset]);

        return $this;
    }

    public function hide(): self
    {
        $this->dispatch('Admob.HideBanner');

        return $this;
    }
}

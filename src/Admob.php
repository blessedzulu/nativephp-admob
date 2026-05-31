<?php

declare(strict_types=1);

namespace BlessedZulu\NativePhpAdmob;

use BlessedZulu\NativePhpAdmob\Builders\AppOpenAd;
use BlessedZulu\NativePhpAdmob\Builders\BannerAd;
use BlessedZulu\NativePhpAdmob\Builders\InterstitialAd;
use BlessedZulu\NativePhpAdmob\Builders\RewardedAd;
use BlessedZulu\NativePhpAdmob\Builders\RewardedInterstitialAd;
use BlessedZulu\NativePhpAdmob\Consent\Att;
use BlessedZulu\NativePhpAdmob\Consent\Ump;
use BlessedZulu\NativePhpAdmob\Contracts\Bridge;
use BlessedZulu\NativePhpAdmob\Events\ConsentChanged;
use BlessedZulu\NativePhpAdmob\Support\SlotResolver;

class Admob
{
    protected SlotResolver $resolver;

    protected ?Ump $ump = null;

    protected ?Att $att = null;

    /**
     * Internal PHP-side cache of the consent state. Updated by the
     * ConsentChanged event listener registered in the service provider.
     *
     * Defaults to null which is interpreted as "consent has not yet been
     * resolved" - canRequestAds() returns false in that state. The fake
     * bridge can flip this directly via setCanRequestAds() in tests.
     */
    protected ?bool $cachedCanRequestAds = null;

    /**
     * @param  array<string, mixed>  $config  The 'admob' config array
     */
    public function __construct(
        protected Bridge $bridge,
        protected array $config,
    ) {
        $this->resolver = new SlotResolver($config);
    }

    public function start(): void
    {
        $this->bridge->call('Admob.Start', [
            'ump_enabled' => (bool) ($this->config['consent']['ump_enabled'] ?? true),
            'att_enabled' => (bool) ($this->config['consent']['att_enabled'] ?? true),
            'app_id' => (string) ($this->config['app_id'] ?? ''),
            'test_devices' => $this->config['test_devices'] ?? [],
        ]);
    }

    public function canRequestAds(): bool
    {
        return $this->cachedCanRequestAds === true;
    }

    public function isReady(): bool
    {
        return $this->canRequestAds();
    }

    public function setCanRequestAds(bool $value): void
    {
        $this->cachedCanRequestAds = $value;
    }

    public function onConsentChanged(string $status): void
    {
        $this->cachedCanRequestAds = match ($status) {
            ConsentChanged::STATUS_OBTAINED,
            ConsentChanged::STATUS_NOT_REQUIRED => true,
            default => false,
        };
    }

    public function banner(string $slot): BannerAd
    {
        return new BannerAd($this->bridge, $this, $slot, $this->resolver->resolve(BannerAd::FORMAT, $slot));
    }

    public function interstitial(string $slot): InterstitialAd
    {
        return new InterstitialAd($this->bridge, $this, $slot, $this->resolver->resolve(InterstitialAd::FORMAT, $slot));
    }

    public function rewarded(string $slot): RewardedAd
    {
        return new RewardedAd($this->bridge, $this, $slot, $this->resolver->resolve(RewardedAd::FORMAT, $slot));
    }

    public function rewardedInterstitial(string $slot): RewardedInterstitialAd
    {
        return new RewardedInterstitialAd($this->bridge, $this, $slot, $this->resolver->resolve(RewardedInterstitialAd::FORMAT, $slot));
    }

    public function appOpen(string $slot): AppOpenAd
    {
        return new AppOpenAd($this->bridge, $this, $slot, $this->resolver->resolve(AppOpenAd::FORMAT, $slot));
    }

    public function ump(): Ump
    {
        return $this->ump ??= new Ump($this->bridge);
    }

    public function att(): Att
    {
        return $this->att ??= new Att($this->bridge);
    }

    public function bridge(): Bridge
    {
        return $this->bridge;
    }
}

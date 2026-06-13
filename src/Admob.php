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
use BlessedZulu\NativePhpAdmob\Exceptions\UnknownSlotException;
use BlessedZulu\NativePhpAdmob\Support\FrequencyCap;
use BlessedZulu\NativePhpAdmob\Support\SlotResolver;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Cache;

class Admob
{
    protected SlotResolver $resolver;

    protected ?Ump $ump = null;

    protected ?Att $att = null;

    protected ?FrequencyCap $frequencyCap = null;

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
     * Test/runtime override for the enabled kill-switch. Null falls back to
     * config('admob.enabled'). Admob::fake() sets this true so call-recording
     * tests work without extra setup.
     */
    protected ?bool $enabledOverride = null;

    /**
     * Lazily-resolved platform ('ios'|'android'|null), cached for the process.
     * Only used to pick the right test ad unit ID where formats diverge.
     */
    protected ?string $platform = null;

    protected bool $platformResolved = false;

    /**
     * @param  array<string, mixed>  $config  The 'admob' config array
     */
    public function __construct(
        protected Bridge $bridge,
        protected array $config,
        protected ?CacheRepository $cache = null,
    ) {
        $this->resolver = new SlotResolver($config);
    }

    public function frequencyCap(): FrequencyCap
    {
        return $this->frequencyCap ??= new FrequencyCap(
            $this->cache ?? Cache::store($this->config['frequency_store'] ?? null),
            $this->config,
        );
    }

    public function start(): void
    {
        $this->bridge->call('Admob.Start', [
            'ump_enabled' => (bool) ($this->config['consent']['ump_enabled'] ?? true),
            'att_enabled' => (bool) ($this->config['consent']['att_enabled'] ?? true),
            'app_id' => $this->appId(),
            'test_devices' => $this->config['test_devices'] ?? [],
        ]);
    }

    public function enabled(): bool
    {
        return $this->enabledOverride ?? (bool) ($this->config['enabled'] ?? false);
    }

    public function setEnabled(bool $value): void
    {
        $this->enabledOverride = $value;
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

    /**
     * Resolve the running platform once via the Admob.Platform bridge call and
     * cache it. Used to pick platform-specific ad unit IDs - production slots and
     * the app ID may carry an ['android' => ..., 'ios' => ...] array, and some
     * test IDs (e.g. App Open) diverge across platforms.
     */
    protected function platform(): ?string
    {
        if (! $this->platformResolved) {
            $response = $this->bridge->call('Admob.Platform');
            $this->platform = $response['data']['platform'] ?? null;
            $this->platformResolved = true;
        }

        return $this->platform;
    }

    /**
     * The AdMob app ID for the running platform. A platform-keyed array
     * (['android' => '...', 'ios' => '...']) resolves to the current platform; a
     * plain string is used as-is (universal / single-platform).
     */
    protected function appId(): string
    {
        $appId = $this->config['app_id'] ?? '';

        if (is_array($appId)) {
            $appId = $appId[$this->platform()] ?? '';
        }

        return (string) $appId;
    }

    /**
     * The platform to resolve a slot for, or null when it isn't needed. Only
     * platform-keyed slots (arrays) and platform-divergent test IDs (App Open in
     * test mode) require it, so a plain string slot never pays the cached
     * Admob.Platform bridge call.
     */
    protected function platformFor(string $format, string $slot): ?string
    {
        $needsPlatform = is_array($this->config['slots'][$format][$slot] ?? null)
            || (($this->config['test_mode'] ?? false) && $format === AppOpenAd::FORMAT);

        return $needsPlatform ? $this->platform() : null;
    }

    public function banner(string $slot): BannerAd
    {
        return new BannerAd($this->bridge, $this, $slot, $this->resolver->resolve(BannerAd::FORMAT, $slot, $this->platformFor(BannerAd::FORMAT, $slot)));
    }

    public function interstitial(string $slot): InterstitialAd
    {
        return new InterstitialAd($this->bridge, $this, $slot, $this->resolver->resolve(InterstitialAd::FORMAT, $slot, $this->platformFor(InterstitialAd::FORMAT, $slot)));
    }

    public function rewarded(string $slot): RewardedAd
    {
        return new RewardedAd($this->bridge, $this, $slot, $this->resolver->resolve(RewardedAd::FORMAT, $slot, $this->platformFor(RewardedAd::FORMAT, $slot)));
    }

    public function rewardedInterstitial(string $slot): RewardedInterstitialAd
    {
        return new RewardedInterstitialAd($this->bridge, $this, $slot, $this->resolver->resolve(RewardedInterstitialAd::FORMAT, $slot, $this->platformFor(RewardedInterstitialAd::FORMAT, $slot)));
    }

    public function appOpen(string $slot): AppOpenAd
    {
        return new AppOpenAd($this->bridge, $this, $slot, $this->resolver->resolve(AppOpenAd::FORMAT, $slot, $this->platformFor(AppOpenAd::FORMAT, $slot)));
    }

    /**
     * Suppress (or re-enable) the native app-open auto-show. App-open ads present
     * themselves on foreground outside any per-request gate, so call this with
     * true while the user should see no ads (e.g. a temporary ad-free pass) and
     * false to restore. The flag lives in the native layer and resets on app
     * restart, so re-sync it at boot.
     */
    public function setAppOpenSuppressed(bool $suppressed): void
    {
        $this->bridge->call('Admob.SetAppOpenSuppressed', ['suppressed' => $suppressed]);
    }

    /**
     * The ad unit ID that would be used right now for a slot - the test unit in
     * test mode, otherwise the platform-resolved configured unit - or null if the
     * slot isn't configured. Non-throwing, so callers can gate UI on "is this set
     * up?" without catching exceptions or reaching into config('admob.slots.*').
     */
    public function adUnit(string $format, string $slot): ?string
    {
        try {
            return $this->resolver->resolve($format, $slot, $this->platformFor($format, $slot));
        } catch (UnknownSlotException) {
            return null;
        }
    }

    /**
     * Whether a slot resolves to a usable ad unit for the running platform.
     */
    public function hasSlot(string $format, string $slot): bool
    {
        return $this->adUnit($format, $slot) !== null;
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

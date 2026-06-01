<?php

declare(strict_types=1);

namespace BlessedZulu\NativePhpAdmob\Facades;

use BlessedZulu\NativePhpAdmob\Admob as AdmobManager;
use BlessedZulu\NativePhpAdmob\Contracts\Bridge;
use BlessedZulu\NativePhpAdmob\Support\FakeBridge;
use Illuminate\Support\Facades\Facade;

/**
 * @method static void start()
 * @method static bool canRequestAds()
 * @method static bool isReady()
 * @method static \BlessedZulu\NativePhpAdmob\Builders\BannerAd banner(string $slot)
 * @method static \BlessedZulu\NativePhpAdmob\Builders\InterstitialAd interstitial(string $slot)
 * @method static \BlessedZulu\NativePhpAdmob\Builders\RewardedAd rewarded(string $slot)
 * @method static \BlessedZulu\NativePhpAdmob\Builders\RewardedInterstitialAd rewardedInterstitial(string $slot)
 * @method static \BlessedZulu\NativePhpAdmob\Builders\AppOpenAd appOpen(string $slot)
 * @method static \BlessedZulu\NativePhpAdmob\Consent\Ump ump()
 * @method static \BlessedZulu\NativePhpAdmob\Consent\Att att()
 */
class Admob extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'admob';
    }

    /**
     * Swap the bound bridge for a FakeBridge. Returns the fake so tests can
     * assert against recorded calls and dispatch simulated events.
     *
     * The fake defaults to canRequestAds=true so show() flows work without
     * extra setup. Use $fake->setCanRequestAds(false) or chain
     * Admob::fake()->withoutConsent() to test the gated path.
     */
    public static function fake(): FakeBridge
    {
        $fake = new FakeBridge;

        $app = static::getFacadeApplication();

        $app->instance(Bridge::class, $fake);

        $config = (array) $app['config']->get('admob', []);
        $manager = new AdmobManager(
            $fake,
            $config,
            $app['cache']->store($config['frequency_store'] ?? null),
        );
        $manager->setCanRequestAds(true);

        $app->instance('admob', $manager);

        // Reset the static facade resolution cache so subsequent calls
        // get the new manager.
        static::clearResolvedInstance('admob');

        return $fake;
    }
}

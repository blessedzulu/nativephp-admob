<?php

declare(strict_types=1);

namespace BlessedZulu\NativePhpAdmob\Support;

use InvalidArgumentException;

/**
 * Google's reserved test ad unit IDs.
 *
 * These IDs always serve safe demo ads and are how Google asks developers to
 * test integrations without risking policy violations. They are universal - 
 * the same IDs work across every AdMob account, every app, every region.
 *
 * Source: https://developers.google.com/admob/android/test-ads
 *         https://developers.google.com/admob/ios/test-ads
 */
class TestAdUnits
{
    public const BANNER = 'ca-app-pub-3940256099942544/6300978111';

    public const INTERSTITIAL = 'ca-app-pub-3940256099942544/1033173712';

    public const REWARDED = 'ca-app-pub-3940256099942544/5224354917';

    public const REWARDED_INTERSTITIAL = 'ca-app-pub-3940256099942544/5354046379';

    // App Open test IDs are the only ones that differ per platform.
    public const APP_OPEN_ANDROID = 'ca-app-pub-3940256099942544/9257395921';

    public const APP_OPEN_IOS = 'ca-app-pub-3940256099942544/5662855259';

    /*
     * Back-compat default for callers that don't pass a platform - resolves to
     * the Android App Open ID. Platform-aware callers should use forPlatform().
     */
    public const APP_OPEN = self::APP_OPEN_ANDROID;

    public static function for(string $format): string
    {
        return match ($format) {
            'banner' => self::BANNER,
            'interstitial' => self::INTERSTITIAL,
            'rewarded' => self::REWARDED,
            'rewarded_interstitial' => self::REWARDED_INTERSTITIAL,
            'app_open' => self::APP_OPEN,
            default => throw new InvalidArgumentException("Unknown ad format [{$format}]."),
        };
    }

    /**
     * Like for(), but resolves the platform-specific test ID where it matters.
     * Only App Open diverges across platforms; every other format is universal,
     * so they fall through to for(). $platform is 'ios' | 'android' | null.
     */
    public static function forPlatform(string $format, ?string $platform): string
    {
        if ($format === 'app_open') {
            return $platform === 'ios' ? self::APP_OPEN_IOS : self::APP_OPEN_ANDROID;
        }

        return self::for($format);
    }
}

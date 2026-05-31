<?php

declare(strict_types=1);

namespace BlessedZulu\NativePhpAdmob\Support;

use InvalidArgumentException;

/**
 * Google's reserved test ad unit IDs.
 *
 * These IDs always serve safe demo ads and are how Google asks developers to
 * test integrations without risking policy violations. They are universal —
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

    public const APP_OPEN = 'ca-app-pub-3940256099942544/3419835294';

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
}

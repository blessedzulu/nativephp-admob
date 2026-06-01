<?php

declare(strict_types=1);

use BlessedZulu\NativePhpAdmob\Facades\Admob;
use BlessedZulu\NativePhpAdmob\Support\SlotResolver;
use BlessedZulu\NativePhpAdmob\Support\TestAdUnits;

it('resolves the platform-specific App Open test ID', function () {
    expect(TestAdUnits::forPlatform('app_open', 'ios'))->toBe(TestAdUnits::APP_OPEN_IOS)
        ->and(TestAdUnits::forPlatform('app_open', 'android'))->toBe(TestAdUnits::APP_OPEN_ANDROID)
        ->and(TestAdUnits::forPlatform('app_open', null))->toBe(TestAdUnits::APP_OPEN_ANDROID);
});

it('keeps non-App-Open formats platform-agnostic', function () {
    foreach (['banner', 'interstitial', 'rewarded', 'rewarded_interstitial'] as $format) {
        expect(TestAdUnits::forPlatform($format, 'ios'))->toBe(TestAdUnits::for($format))
            ->and(TestAdUnits::forPlatform($format, 'android'))->toBe(TestAdUnits::for($format));
    }
});

it('keeps the back-compat APP_OPEN constant pointing at Android', function () {
    expect(TestAdUnits::APP_OPEN)->toBe(TestAdUnits::APP_OPEN_ANDROID);
});

it('passes the platform through SlotResolver in test_mode', function () {
    $resolver = new SlotResolver(['test_mode' => true]);

    expect($resolver->resolve('app_open', 'x', 'ios'))->toBe(TestAdUnits::APP_OPEN_IOS)
        ->and($resolver->resolve('app_open', 'x', 'android'))->toBe(TestAdUnits::APP_OPEN_ANDROID)
        ->and($resolver->resolve('app_open', 'x'))->toBe(TestAdUnits::APP_OPEN_ANDROID);
});

it('uses the iOS App Open test ID end to end when the bridge reports ios', function () {
    config(['admob.test_mode' => true]);
    $fake = Admob::fake()->setPlatform('ios');

    Admob::appOpen('cold_start')->load();

    $fake->assertCalled('Admob.LoadAppOpen', fn ($params) => $params['unit_id'] === TestAdUnits::APP_OPEN_IOS);
});

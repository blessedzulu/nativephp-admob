<?php

declare(strict_types=1);

use BlessedZulu\NativePhpAdmob\Facades\Admob;

beforeEach(function () {
    config([
        'admob.test_mode' => false,
        'admob.slots.interstitial.between_calcs' => 'ca-app-pub-X/Y',
    ]);
});

it('loads an interstitial', function () {
    $fake = Admob::fake();

    Admob::interstitial('between_calcs')->load();

    $fake->assertCalled('Admob.LoadInterstitial');
});

it('reports readiness from the bridge response', function () {
    $fake = Admob::fake();
    $fake->stub('Admob.InterstitialReady', [
        'success' => true,
        'data' => ['ready' => true],
        'error' => null,
    ]);

    expect(Admob::interstitial('between_calcs')->isReady())->toBeTrue();
});

it('returns false readiness when no data is present', function () {
    Admob::fake();

    expect(Admob::interstitial('between_calcs')->isReady())->toBeFalse();
});

it('shows an interstitial when consent is granted', function () {
    $fake = Admob::fake();

    Admob::interstitial('between_calcs')->show();

    $fake->assertCalled('Admob.ShowInterstitial');
});

it('skips show when consent is not granted', function () {
    $fake = Admob::fake();
    app('admob')->setCanRequestAds(false);

    Admob::interstitial('between_calcs')->show();

    $fake->assertNotCalled('Admob.ShowInterstitial');
});

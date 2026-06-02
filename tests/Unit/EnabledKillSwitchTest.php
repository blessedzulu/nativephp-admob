<?php

declare(strict_types=1);

use BlessedZulu\NativePhpAdmob\Facades\Admob;

beforeEach(function () {
    config(['admob.test_mode' => true]);
});

it('no-ops ad load/show/hide when disabled', function () {
    $fake = Admob::fake();
    app('admob')->setEnabled(false);

    Admob::banner('x')->load()->show('bottom')->hide();
    Admob::interstitial('x')->load();

    $fake->assertNotCalled('Admob.LoadBanner');
    $fake->assertNotCalled('Admob.ShowBanner');
    $fake->assertNotCalled('Admob.HideBanner');
    $fake->assertNotCalled('Admob.LoadInterstitial');
});

it('reports not-ready when disabled', function () {
    Admob::fake()->stub('Admob.InterstitialReady', ['success' => true, 'data' => ['ready' => true], 'error' => null]);
    app('admob')->setEnabled(false);

    expect(Admob::interstitial('x')->isReady())->toBeFalse();
});

it('still gathers consent when ads are disabled', function () {
    $fake = Admob::fake();
    app('admob')->setEnabled(false);

    Admob::ump()->requestConsentInfo();
    Admob::att()->status();

    $fake->assertCalled('Admob.UmpRequestInfo');
});

it('dispatches ad calls when enabled', function () {
    $fake = Admob::fake(); // fake() enables by default

    Admob::banner('x')->load()->show('bottom');

    $fake->assertCalled('Admob.LoadBanner');
    $fake->assertCalled('Admob.ShowBanner');
});

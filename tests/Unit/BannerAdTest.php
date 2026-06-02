<?php

declare(strict_types=1);

use BlessedZulu\NativePhpAdmob\Builders\BannerAd;
use BlessedZulu\NativePhpAdmob\Facades\Admob;

beforeEach(function () {
    config(['admob.test_mode' => false, 'admob.slots.banner.footer' => 'ca-app-pub-X/Y']);
});

it('loads a banner via the bridge', function () {
    $fake = Admob::fake();

    Admob::banner('footer')->load();

    $fake->assertCalled('Admob.LoadBanner', fn (array $p) => $p['slot'] === 'footer' && $p['unit_id'] === 'ca-app-pub-X/Y');
});

it('shows a banner at the requested position', function () {
    $fake = Admob::fake();

    Admob::banner('footer')->show('top');

    $fake->assertCalled('Admob.ShowBanner', fn (array $p) => $p['position'] === 'top');
});

it('passes an explicit offset through to show', function () {
    $fake = Admob::fake();

    Admob::banner('footer')->show('bottom', 56);

    $fake->assertCalled('Admob.ShowBanner', fn (array $p) => $p['position'] === 'bottom' && $p['offset'] === 56);
});

it('falls back to the configured per-position offset', function () {
    config(['admob.banner.offset.bottom' => 72]);
    $fake = Admob::fake();

    Admob::banner('footer')->show('bottom');

    $fake->assertCalled('Admob.ShowBanner', fn (array $p) => $p['offset'] === 72);
});

it('hides a banner', function () {
    $fake = Admob::fake();

    Admob::banner('footer')->hide();

    $fake->assertCalled('Admob.HideBanner');
});

it('chains load and show fluently', function () {
    $fake = Admob::fake();

    $result = Admob::banner('footer')->load()->show();

    expect($result)->toBeInstanceOf(BannerAd::class);
    $fake->assertCalled('Admob.LoadBanner');
    $fake->assertCalled('Admob.ShowBanner');
});

it('skips show when consent is not granted', function () {
    $fake = Admob::fake();
    app('admob')->setCanRequestAds(false);

    Admob::banner('footer')->show();

    $fake->assertNotCalled('Admob.ShowBanner');
});

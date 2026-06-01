<?php

declare(strict_types=1);

use BlessedZulu\NativePhpAdmob\Contracts\Bridge;
use BlessedZulu\NativePhpAdmob\Events\ConsentChanged;
use BlessedZulu\NativePhpAdmob\Facades\Admob;
use BlessedZulu\NativePhpAdmob\Support\FakeBridge;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    config([
        'admob.test_mode' => false,
        'admob.slots.banner.footer' => 'ca-app-pub-X/Y',
    ]);
});

it('swaps the bound Bridge to a FakeBridge', function () {
    $fake = Admob::fake();

    expect(app(Bridge::class))->toBeInstanceOf(FakeBridge::class)
        ->and($fake)->toBeInstanceOf(FakeBridge::class)
        ->and(app(Bridge::class))->toBe($fake);
});

it('starts the fake with consent granted by default', function () {
    Admob::fake();

    expect(Admob::canRequestAds())->toBeTrue();
});

it('records calls across multiple ad formats', function () {
    config(['admob.slots.interstitial.x' => 'ca-app-pub-X/Y']);
    $fake = Admob::fake();

    Admob::banner('footer')->load();
    Admob::interstitial('x')->load();

    expect($fake->calls)->toHaveCount(2);
});

it('responds to a simulated ConsentChanged event by updating canRequestAds', function () {
    $fake = Admob::fake();

    $fake->simulateEvent(ConsentChanged::class, [ConsentChanged::STATUS_REQUIRED]);

    expect(Admob::canRequestAds())->toBeFalse();

    $fake->simulateEvent(ConsentChanged::class, [ConsentChanged::STATUS_OBTAINED]);

    expect(Admob::canRequestAds())->toBeTrue();
});

it('logs a warning and no-ops when show() is called without consent', function () {
    $fake = Admob::fake();
    app('admob')->setCanRequestAds(false);
    Log::spy();

    Admob::banner('footer')->show();

    $fake->assertNotCalled('Admob.ShowBanner');
    Log::shouldHaveReceived('warning')->once();
});

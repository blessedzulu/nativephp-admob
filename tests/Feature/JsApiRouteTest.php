<?php

declare(strict_types=1);

// The JS module (resources/js/admob.js) is not PHP-unit-tested; these cover the
// route + controller it talks to, asserting full facade parity.

use BlessedZulu\NativePhpAdmob\Events\AdShowThrottled;
use BlessedZulu\NativePhpAdmob\Facades\Admob;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->withoutMiddleware(VerifyCsrfToken::class);
    config(['admob.test_mode' => true]);
});

it('loads, shows, and hides a banner through the facade', function () {
    $fake = Admob::fake();

    $this->postJson('/_admob/call', ['kind' => 'ad', 'format' => 'banner', 'slot' => 'home_footer', 'action' => 'load'])
        ->assertOk()->assertJson(['ok' => true]);
    $this->postJson('/_admob/call', ['kind' => 'ad', 'format' => 'banner', 'slot' => 'home_footer', 'action' => 'show', 'position' => 'top'])
        ->assertOk();
    $this->postJson('/_admob/call', ['kind' => 'ad', 'format' => 'banner', 'slot' => 'home_footer', 'action' => 'hide'])
        ->assertOk();

    $fake->assertCalled('Admob.LoadBanner', fn ($p) => $p['slot'] === 'home_footer');
    $fake->assertCalled('Admob.ShowBanner', fn ($p) => $p['position'] === 'top');
    $fake->assertCalled('Admob.HideBanner');
});

it('reports interstitial readiness', function () {
    Admob::fake()->stub('Admob.InterstitialReady', ['success' => true, 'data' => ['ready' => true], 'error' => null]);

    $this->postJson('/_admob/call', ['kind' => 'ad', 'format' => 'interstitial', 'slot' => 'x', 'action' => 'isReady'])
        ->assertOk()->assertJson(['ready' => true]);
});

it('honors the consent gate (show no-ops, still 200)', function () {
    $fake = Admob::fake();
    app('admob')->setCanRequestAds(false);

    $this->postJson('/_admob/call', ['kind' => 'ad', 'format' => 'interstitial', 'slot' => 'x', 'action' => 'show'])
        ->assertOk()->assertJson(['ok' => true]);

    $fake->assertNotCalled('Admob.ShowInterstitial');
});

it('honors the enabled kill-switch', function () {
    $fake = Admob::fake();
    app('admob')->setEnabled(false);

    $this->postJson('/_admob/call', ['kind' => 'ad', 'format' => 'banner', 'slot' => 'x', 'action' => 'load'])->assertOk();

    $fake->assertNotCalled('Admob.LoadBanner');
});

it('throttles a second show via the frequency cap and dispatches AdShowThrottled', function () {
    config([
        'admob.test_mode' => false,
        'admob.slots.interstitial.x' => 'ca-app-pub-1/2',
        'admob.frequency.interstitial' => ['min_interval_seconds' => 60],
    ]);
    Event::fake([AdShowThrottled::class]);
    $fake = Admob::fake();

    $this->postJson('/_admob/call', ['kind' => 'ad', 'format' => 'interstitial', 'slot' => 'x', 'action' => 'show'])->assertOk();
    $this->postJson('/_admob/call', ['kind' => 'ad', 'format' => 'interstitial', 'slot' => 'x', 'action' => 'show'])->assertOk();

    $fake->assertCalledTimes('Admob.ShowInterstitial', 1);
    Event::assertDispatched(AdShowThrottled::class);
});

it('proxies ump and att actions', function () {
    $fake = Admob::fake()->stub('Admob.UmpStatus', ['success' => true, 'data' => ['status' => 'obtained'], 'error' => null]);

    $this->postJson('/_admob/call', ['kind' => 'ump', 'action' => 'requestInfo'])->assertOk()->assertJson(['ok' => true]);
    $this->postJson('/_admob/call', ['kind' => 'ump', 'action' => 'status'])->assertOk()->assertJson(['status' => 'obtained']);

    $fake->assertCalled('Admob.UmpRequestInfo');
});

it('rejects unknown kind or action with 422', function () {
    Admob::fake();

    $this->postJson('/_admob/call', ['kind' => 'nope', 'action' => 'x'])->assertStatus(422)->assertJson(['ok' => false]);
    $this->postJson('/_admob/call', ['kind' => 'ad', 'format' => 'banner', 'slot' => 'x', 'action' => 'frobnicate'])->assertStatus(422);
    $this->postJson('/_admob/call', ['kind' => 'ad', 'format' => 'banner', 'slot' => '', 'action' => 'load'])->assertStatus(422);
});

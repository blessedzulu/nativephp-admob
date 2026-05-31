<?php

declare(strict_types=1);

use BlessedZulu\NativePhpAdmob\Events\ConsentChanged;
use BlessedZulu\NativePhpAdmob\Facades\Admob;

it('requests consent info via the bridge', function () {
    $fake = Admob::fake();

    Admob::ump()->requestConsentInfo();

    $fake->assertCalled('Admob.UmpRequestInfo');
});

it('shows the consent form when required', function () {
    $fake = Admob::fake();

    Admob::ump()->showFormIfRequired();

    $fake->assertCalled('Admob.UmpShowForm');
});

it('returns the can-request-ads flag from the bridge', function () {
    $fake = Admob::fake();
    $fake->stub('Admob.UmpCanRequestAds', [
        'success' => true,
        'data' => ['can_request' => true],
        'error' => null,
    ]);

    expect(Admob::ump()->canRequestAds())->toBeTrue();
});

it('returns the status string from the bridge', function () {
    $fake = Admob::fake();
    $fake->stub('Admob.UmpStatus', [
        'success' => true,
        'data' => ['status' => ConsentChanged::STATUS_OBTAINED],
        'error' => null,
    ]);

    expect(Admob::ump()->status())->toBe('obtained');
});

it('falls back to unknown status when nothing is returned', function () {
    Admob::fake();

    expect(Admob::ump()->status())->toBe(ConsentChanged::STATUS_UNKNOWN);
});

it('resets consent state', function () {
    $fake = Admob::fake();

    Admob::ump()->reset();

    $fake->assertCalled('Admob.UmpReset');
});

<?php

declare(strict_types=1);

use BlessedZulu\NativePhpAdmob\Consent\Att;
use BlessedZulu\NativePhpAdmob\Facades\Admob;

it('returns unsupported on Android and skips the request', function () {
    $fake = Admob::fake();
    $fake->stub('Admob.Platform', [
        'success' => true,
        'data' => ['platform' => 'android'],
        'error' => null,
    ]);

    Admob::att()->requestAuthorization();

    expect(Admob::att()->status())->toBe(Att::STATUS_UNSUPPORTED);
    $fake->assertNotCalled('Admob.AttRequest');
});

it('asks for authorization on iOS', function () {
    $fake = Admob::fake();
    $fake->stub('Admob.Platform', [
        'success' => true,
        'data' => ['platform' => 'ios'],
        'error' => null,
    ]);

    Admob::att()->requestAuthorization();

    $fake->assertCalled('Admob.AttRequest');
});

it('returns the status string from the bridge on iOS', function () {
    $fake = Admob::fake();
    $fake->stub('Admob.Platform', [
        'success' => true,
        'data' => ['platform' => 'ios'],
        'error' => null,
    ]);
    $fake->stub('Admob.AttStatus', [
        'success' => true,
        'data' => ['status' => Att::STATUS_AUTHORIZED],
        'error' => null,
    ]);

    expect(Admob::att()->status())->toBe(Att::STATUS_AUTHORIZED);
});

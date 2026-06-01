<?php

declare(strict_types=1);

use BlessedZulu\NativePhpAdmob\Facades\Admob;
use Illuminate\Support\Facades\Log;

it('logs a warning when a bridge call fails, without throwing', function () {
    config(['admob.test_mode' => true]);
    Log::spy();
    $fake = Admob::fake()->stub('Admob.ShowInterstitial', [
        'success' => false, 'data' => null, 'error' => 'no fill',
    ]);

    Admob::interstitial('x')->show();

    $fake->assertCalled('Admob.ShowInterstitial');
    Log::shouldHaveReceived('warning')->atLeast()->once();
});

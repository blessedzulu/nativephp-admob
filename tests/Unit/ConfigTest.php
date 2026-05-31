<?php

declare(strict_types=1);
use BlessedZulu\NativePhpAdmob\Admob;

it('merges package config defaults', function () {
    expect(config('admob.enabled'))->toBeFalse()
        ->and(config('admob.app_id'))->toBe('')
        ->and(config('admob.slots'))->toBeArray()
        ->and(config('admob.slots.banner'))->toBeArray()
        ->and(config('admob.slots.interstitial'))->toBeArray()
        ->and(config('admob.slots.rewarded'))->toBeArray()
        ->and(config('admob.slots.rewarded_interstitial'))->toBeArray()
        ->and(config('admob.slots.app_open'))->toBeArray()
        ->and(config('admob.consent.ump_enabled'))->toBeTrue()
        ->and(config('admob.consent.att_enabled'))->toBeTrue();
});

it('resolves the facade through the container', function () {
    expect(app('admob'))->toBeInstanceOf(Admob::class)
        ->and(BlessedZulu\NativePhpAdmob\Facades\Admob::getFacadeRoot())
        ->toBeInstanceOf(Admob::class);
});

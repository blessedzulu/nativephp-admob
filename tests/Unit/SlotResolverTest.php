<?php

declare(strict_types=1);

use BlessedZulu\NativePhpAdmob\Exceptions\UnknownSlotException;
use BlessedZulu\NativePhpAdmob\Support\SlotResolver;
use BlessedZulu\NativePhpAdmob\Support\TestAdUnits;

it('resolves a configured slot to its ad unit ID', function () {
    $resolver = new SlotResolver([
        'test_mode' => false,
        'slots' => ['banner' => ['footer' => 'ca-app-pub-X/Y']],
    ]);

    expect($resolver->resolve('banner', 'footer'))->toBe('ca-app-pub-X/Y');
});

it('returns the Google test ID when test_mode is on regardless of config', function () {
    $resolver = new SlotResolver([
        'test_mode' => true,
        'slots' => ['banner' => ['footer' => 'ca-app-pub-X/Y']],
    ]);

    expect($resolver->resolve('banner', 'footer'))->toBe(TestAdUnits::BANNER);
});

it('throws UnknownSlotException for a missing slot when test_mode is off', function () {
    $resolver = new SlotResolver([
        'test_mode' => false,
        'slots' => ['banner' => []],
    ]);

    $resolver->resolve('banner', 'missing');
})->throws(UnknownSlotException::class);

it('treats an empty-string unit as missing', function () {
    $resolver = new SlotResolver([
        'test_mode' => false,
        'slots' => ['banner' => ['footer' => '']],
    ]);

    $resolver->resolve('banner', 'footer');
})->throws(UnknownSlotException::class);

it('returns the right test ID for every format', function () {
    $resolver = new SlotResolver(['test_mode' => true]);

    expect($resolver->resolve('banner', 'x'))->toBe(TestAdUnits::BANNER)
        ->and($resolver->resolve('interstitial', 'x'))->toBe(TestAdUnits::INTERSTITIAL)
        ->and($resolver->resolve('rewarded', 'x'))->toBe(TestAdUnits::REWARDED)
        ->and($resolver->resolve('rewarded_interstitial', 'x'))->toBe(TestAdUnits::REWARDED_INTERSTITIAL)
        ->and($resolver->resolve('app_open', 'x'))->toBe(TestAdUnits::APP_OPEN);
});

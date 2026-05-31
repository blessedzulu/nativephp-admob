<?php

declare(strict_types=1);

use BlessedZulu\NativePhpAdmob\Support\FakeBridge;

it('records bridge calls in order with their params', function () {
    $bridge = new FakeBridge;

    $bridge->call('Admob.LoadBanner', ['slot' => 'a']);
    $bridge->call('Admob.LoadInterstitial', ['slot' => 'b']);

    expect($bridge->calls)->toHaveCount(2)
        ->and($bridge->calls[0]['method'])->toBe('Admob.LoadBanner')
        ->and($bridge->calls[0]['params'])->toBe(['slot' => 'a'])
        ->and($bridge->calls[1]['method'])->toBe('Admob.LoadInterstitial');
});

it('returns the default response shape when nothing is stubbed', function () {
    $bridge = new FakeBridge;

    $response = $bridge->call('Admob.LoadBanner');

    expect($response)->toBe(['success' => true, 'data' => null, 'error' => null]);
});

it('returns a stubbed response for a specific method', function () {
    $bridge = (new FakeBridge)->stub('Admob.InterstitialReady', [
        'success' => true,
        'data' => ['ready' => true],
        'error' => null,
    ]);

    $response = $bridge->call('Admob.InterstitialReady');

    expect($response['data']['ready'])->toBeTrue();
});

it('asserts a call was made', function () {
    $bridge = new FakeBridge;
    $bridge->call('Admob.LoadBanner');

    $bridge->assertCalled('Admob.LoadBanner');
});

it('asserts a call was not made', function () {
    $bridge = new FakeBridge;

    $bridge->assertNotCalled('Admob.LoadInterstitial');
});

it('asserts a method was called a specific number of times', function () {
    $bridge = new FakeBridge;

    $bridge->call('Admob.LoadBanner');
    $bridge->call('Admob.LoadBanner');

    $bridge->assertCalledTimes('Admob.LoadBanner', 2);
});

it('matches calls with a custom predicate', function () {
    $bridge = new FakeBridge;
    $bridge->call('Admob.LoadBanner', ['slot' => 'footer']);

    $bridge->assertCalled(
        'Admob.LoadBanner',
        fn (array $params) => $params['slot'] === 'footer'
    );
});

it('resets all state', function () {
    $bridge = new FakeBridge;
    $bridge->call('Admob.LoadBanner');
    $bridge->stub('Admob.Anything', ['success' => true]);

    $bridge->reset();

    expect($bridge->calls)->toBeEmpty()
        ->and($bridge->stubs)->toBeEmpty();
});

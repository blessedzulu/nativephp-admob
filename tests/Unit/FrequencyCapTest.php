<?php

declare(strict_types=1);

use BlessedZulu\NativePhpAdmob\Events\AdShowThrottled;
use BlessedZulu\NativePhpAdmob\Facades\Admob;
use BlessedZulu\NativePhpAdmob\Support\FrequencyCap;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;

function makeCap(array $frequency = [], bool $testMode = false): FrequencyCap
{
    return new FrequencyCap(app('cache')->store('array'), [
        'test_mode' => $testMode,
        'frequency' => $frequency,
    ]);
}

afterEach(fn () => Carbon::setTestNow());

it('allows the first show then blocks within the cooldown', function () {
    Carbon::setTestNow('2026-06-01 12:00:00');
    $cap = makeCap(['interstitial' => ['min_interval_seconds' => 60]]);

    expect($cap->allows('interstitial', 'x'))->toBeTrue();
    $cap->record('interstitial', 'x');

    expect($cap->allows('interstitial', 'x'))->toBeFalse()
        ->and($cap->reason('interstitial', 'x'))->toBe('cooldown');

    Carbon::setTestNow('2026-06-01 12:01:01');
    expect($cap->allows('interstitial', 'x'))->toBeTrue();
});

it('enforces a daily cap and resets the next day', function () {
    Carbon::setTestNow('2026-06-01 09:00:00');
    $cap = makeCap(['rewarded' => ['max_per_day' => 2]]);

    $cap->record('rewarded', 'r');
    $cap->record('rewarded', 'r');
    expect($cap->reason('rewarded', 'r'))->toBe('daily_cap');

    Carbon::setTestNow('2026-06-02 09:00:00');
    expect($cap->allows('rewarded', 'r'))->toBeTrue();
});

it('lets a per-slot rule override the per-format default', function () {
    Carbon::setTestNow('2026-06-01 12:00:00');
    $cap = makeCap([
        'interstitial' => ['min_interval_seconds' => 60],
        'slots' => ['interstitial' => ['quick' => ['min_interval_seconds' => 5]]],
    ]);

    $cap->record('interstitial', 'quick');
    Carbon::setTestNow('2026-06-01 12:00:06');

    expect($cap->allows('interstitial', 'quick'))->toBeTrue();
});

it('bypasses all caps in test_mode', function () {
    $cap = makeCap(['interstitial' => ['min_interval_seconds' => 99999]], testMode: true);

    $cap->record('interstitial', 'x');

    expect($cap->allows('interstitial', 'x'))->toBeTrue();
});

it('throttles a second immediate show end to end and dispatches AdShowThrottled', function () {
    config([
        'admob.test_mode' => false,
        'admob.slots.interstitial.x' => 'ca-app-pub-1/2',
        'admob.frequency.interstitial' => ['min_interval_seconds' => 60],
    ]);
    Event::fake([AdShowThrottled::class]);
    $fake = Admob::fake();

    Admob::interstitial('x')->show();
    $fake->assertCalledTimes('Admob.ShowInterstitial', 1);

    Admob::interstitial('x')->show();
    $fake->assertCalledTimes('Admob.ShowInterstitial', 1);
    Event::assertDispatched(AdShowThrottled::class);
});

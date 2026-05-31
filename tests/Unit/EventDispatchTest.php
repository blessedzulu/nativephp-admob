<?php

declare(strict_types=1);

use BlessedZulu\NativePhpAdmob\Events;

it('instantiates AdLoaded with slot + format', function () {
    $event = new Events\AdLoaded('footer', 'banner');

    expect($event->slot)->toBe('footer')
        ->and($event->format)->toBe('banner');
});

it('instantiates AdFailedToLoad with the full error payload', function () {
    $event = new Events\AdFailedToLoad('footer', 'banner', 3, 'NO_FILL');

    expect($event->errorCode)->toBe(3)
        ->and($event->errorMessage)->toBe('NO_FILL');
});

it('carries reward payload on UserEarnedReward', function () {
    $event = new Events\UserEarnedReward('export_pdf', 'rewarded', 'coins', 10);

    expect($event->slot)->toBe('export_pdf')
        ->and($event->format)->toBe('rewarded')
        ->and($event->type)->toBe('coins')
        ->and($event->amount)->toBe(10);
});

it('carries a status string on ConsentChanged', function () {
    $event = new Events\ConsentChanged(Events\ConsentChanged::STATUS_OBTAINED);

    expect($event->status)->toBe('obtained');
});

it('instantiates parameterless events', function () {
    expect(new Events\ConsentFormShown)->toBeInstanceOf(Events\ConsentFormShown::class)
        ->and(new Events\TrackingAuthorizationGranted)->toBeInstanceOf(Events\TrackingAuthorizationGranted::class)
        ->and(new Events\TrackingAuthorizationDenied)->toBeInstanceOf(Events\TrackingAuthorizationDenied::class);
});

it('instantiates every remaining ad lifecycle event', function () {
    $classes = [
        Events\AdShown::class,
        Events\AdDismissed::class,
        Events\AdFailedToShow::class,
        Events\AdImpression::class,
        Events\AdClicked::class,
    ];

    foreach ($classes as $class) {
        if (in_array($class, [Events\AdFailedToShow::class], true)) {
            $event = new $class('slot', 'banner', 1, 'reason');
        } else {
            $event = new $class('slot', 'banner');
        }
        expect($event)->toBeInstanceOf($class);
    }
});

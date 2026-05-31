<?php

declare(strict_types=1);

use BlessedZulu\NativePhpAdmob\Events\UserEarnedReward;
use BlessedZulu\NativePhpAdmob\Facades\Admob;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    config([
        'admob.test_mode' => false,
        'admob.slots.rewarded.export_pdf' => 'ca-app-pub-X/Y',
    ]);
});

it('loads + shows a rewarded ad', function () {
    $fake = Admob::fake();

    Admob::rewarded('export_pdf')->load()->show();

    $fake->assertCalled('Admob.LoadRewarded');
    $fake->assertCalled('Admob.ShowRewarded');
});

it('dispatches UserEarnedReward when the bridge simulates one', function () {
    Event::fake([UserEarnedReward::class]);
    $fake = Admob::fake();

    Admob::rewarded('export_pdf')->load()->show();
    $fake->simulateEvent(UserEarnedReward::class, ['export_pdf', 'coins', 10]);

    Event::assertDispatched(
        UserEarnedReward::class,
        fn (UserEarnedReward $e) => $e->slot === 'export_pdf'
            && $e->type === 'coins'
            && $e->amount === 10
    );
});

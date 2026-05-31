<?php

declare(strict_types=1);

use BlessedZulu\NativePhpAdmob\Events\AdLoaded;
use BlessedZulu\NativePhpAdmob\Events\AdShown;
use BlessedZulu\NativePhpAdmob\Events\UserEarnedReward;
use BlessedZulu\NativePhpAdmob\Facades\Admob;
use Illuminate\Support\Facades\Event;

it('completes a full rewarded ad lifecycle end to end', function () {
    config([
        'admob.test_mode' => false,
        'admob.slots.rewarded.export_pdf' => 'ca-app-pub-X/Y',
    ]);

    Event::fake([AdLoaded::class, AdShown::class, UserEarnedReward::class]);

    $fake = Admob::fake();

    Admob::rewarded('export_pdf')->load();
    $fake->simulateEvent(AdLoaded::class, ['export_pdf', 'rewarded']);

    Admob::rewarded('export_pdf')->show();
    $fake->simulateEvent(AdShown::class, ['export_pdf', 'rewarded']);
    $fake->simulateEvent(UserEarnedReward::class, ['export_pdf', 'coins', 10]);

    Event::assertDispatched(AdLoaded::class);
    Event::assertDispatched(AdShown::class);
    Event::assertDispatched(UserEarnedReward::class, fn ($e) => $e->slot === 'export_pdf' && $e->amount === 10);

    $fake->assertCalled('Admob.LoadRewarded');
    $fake->assertCalled('Admob.ShowRewarded');
});

it('uses the Google test ad ID when test_mode is on', function () {
    config(['admob.test_mode' => true, 'admob.slots.banner.footer' => 'ca-app-pub-real/X']);

    $fake = Admob::fake();

    Admob::banner('footer')->load();

    $fake->assertCalled('Admob.LoadBanner', fn (array $p) => str_starts_with((string) $p['unit_id'], 'ca-app-pub-3940256099942544'));
});

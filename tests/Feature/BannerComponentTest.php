<?php

declare(strict_types=1);

use BlessedZulu\NativePhpAdmob\Facades\Admob;

it('fires load + show on render with the right slot and position', function () {
    config(['admob.test_mode' => true]);
    $fake = Admob::fake();

    $this->blade('<x-admob::banner slot="home_footer" position="top" />');

    $fake->assertCalled('Admob.LoadBanner', fn ($p) => $p['slot'] === 'home_footer');
    $fake->assertCalled('Admob.ShowBanner', fn ($p) => $p['slot'] === 'home_footer' && $p['position'] === 'top');
});

it('listens on both window and document for each configured nav event, with cleanup', function () {
    config(['admob.test_mode' => true, 'admob.banner.hide_on_events' => ['inertia:before']]);
    Admob::fake();

    $view = $this->blade('<x-admob::banner slot="home_footer" />');

    $view->assertSee("window.addEventListener('inertia:before'", false)
        ->assertSee("document.addEventListener('inertia:before'", false)
        ->assertSee('_ac.abort()', false)
        ->assertSee('Admob.HideBanner', false);
});

it('emits no nav listeners when hide_on_events is empty', function () {
    config(['admob.test_mode' => true, 'admob.banner.hide_on_events' => []]);
    $fake = Admob::fake();

    $view = $this->blade('<x-admob::banner slot="home_footer" position="bottom" />');

    $view->assertDontSee('addEventListener', false);
    $fake->assertCalled('Admob.LoadBanner');
    $fake->assertCalled('Admob.ShowBanner');
});

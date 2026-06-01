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

it('renders the configured hide-on-navigate listener', function () {
    config(['admob.test_mode' => true, 'admob.banner.hide_on_events' => ['livewire:navigating']]);
    Admob::fake();

    $view = $this->blade('<x-admob::banner slot="home_footer" />');

    $view->assertSee('livewire:navigating.window', false)
        ->assertSee('Admob.HideBanner', false);
});

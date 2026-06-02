<?php

declare(strict_types=1);

// The <x-admob::banner> component is fully client-driven: it renders markup that
// POSTs to /_admob/call once on init (load + show) and on teardown (hide). It no
// longer calls the facade at render time (that caused a reload-on-every-render
// loop), so these assert the emitted markup rather than recorded bridge calls.

it('emits a one-shot mount that loads + shows via the JS endpoint', function () {
    $view = $this->blade('<x-admob::banner slot="home_footer" position="top" />');

    $view->assertSee('_admob', false)            // the /_admob/call endpoint
        ->assertSee('_mount(', false)            // one-shot init, not per-render
        ->assertSee("_call('load')", false)
        ->assertSee("_call('show'", false)
        ->assertSee('home_footer', false)
        ->assertSee('position', false)
        ->assertSee('__admobCallQueue', false);  // calls serialized through the shared queue
});

it('listens on both window and document for each configured nav event, with cleanup', function () {
    config(['admob.banner.hide_on_events' => ['inertia:before']]);

    $view = $this->blade('<x-admob::banner slot="home_footer" />');

    $view->assertSee('window.addEventListener(', false)
        ->assertSee('document.addEventListener(', false)
        ->assertSee('inertia:before', false)
        ->assertSee("_call('hide')", false)
        ->assertSee('_ac.abort()', false);
});

it('still mounts but emits no nav listeners when hide_on_events is empty', function () {
    config(['admob.banner.hide_on_events' => []]);

    $view = $this->blade('<x-admob::banner slot="home_footer" position="bottom" />');

    $view->assertSee('_mount(', false)
        ->assertSee("_call('load')", false)
        ->assertDontSee('addEventListener', false);
});

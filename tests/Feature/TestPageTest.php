<?php

declare(strict_types=1);

// Testbench runs with APP_ENV=testing, so config('admob.test_page') defaults
// true and the route is registered at boot.

it('serves the self-contained test page', function () {
    $response = $this->get('/_admob/test');

    $response->assertOk()
        ->assertSee('AdMob Test', false)
        ->assertSee('native-event', false)        // live event stream wiring
        ->assertSee('_admob', false);             // injected endpoint (slashes JSON-escaped)
});

it('renders controls for every format and consent flow', function () {
    $response = $this->get('/_admob/test');

    $response->assertSee('slot-banner', false)
        ->assertSee('slot-interstitial', false)
        ->assertSee('slot-app_open', false)
        ->assertSee('Consent (UMP)', false)
        ->assertSee('Tracking (ATT', false);
});

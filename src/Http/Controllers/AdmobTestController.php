<?php

declare(strict_types=1);

namespace BlessedZulu\NativePhpAdmob\Http\Controllers;

use Illuminate\Contracts\View\View;

/**
 * Serves the built-in, self-contained AdMob test page. Registered only when
 * config('admob.test_page') is true (default: on outside production). The view
 * has no app-layout / Livewire / CSRF dependency and drives every action
 * through the JS API endpoint, so it works in any NativePHP app.
 */
class AdmobTestController
{
    public function __invoke(): View
    {
        /*
         * In test_mode the ads are Google's demo units (no real consent needed),
         * so allow ads here - otherwise the consent gate would suppress every
         * show() and the test page couldn't display anything.
         */
        if ((bool) config('admob.test_mode', false)) {
            app('admob')->setCanRequestAds(true);
        }

        $prefix = ltrim((string) config('admob.js_api_prefix', '_admob'), '/');

        return view('admob::test-page', [
            'endpoint' => '/'.$prefix.'/call',
            'testMode' => (bool) config('admob.test_mode', false),
        ]);
    }
}

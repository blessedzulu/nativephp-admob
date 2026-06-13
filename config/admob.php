<?php

return [

    /*
     * Master kill-switch. When false, ad load/show/hide all no-op across every
     * format (and the JS API + <admob::banner> too). Consent (UMP) and tracking
     * authorization (ATT) still run, so you can keep gathering consent while ads
     * are toggled off. Even when true, ads only show after consent resolves.
     */
    'enabled' => env('ADMOB_ENABLED', false),

    /*
     * Your AdMob app ID (ca-app-pub-XXXX~YYYY). The app ID is per-platform, so a
     * cross-platform app sets a platform-keyed array and the running platform is
     * picked automatically:
     *
     *   'app_id' => [
     *       'android' => env('ADMOB_APP_ID_ANDROID'),
     *       'ios'     => env('ADMOB_APP_ID_IOS'),
     *   ],
     *
     * A plain string is used as-is (universal / single-platform). The build-time
     * manifest substitution reads ADMOB_APP_ID_ANDROID / ADMOB_APP_ID_IOS (then a
     * universal ADMOB_APP_ID fallback) to fill the manifest + Info.plist per build.
     */
    'app_id' => env('ADMOB_APP_ID', ''),

    /*
     * When true, the SDK is initialised with Google's reserved test ad units
     * regardless of the slot IDs below. Auto-on in non-production, opt-in
     * anywhere else via ADMOB_TEST_MODE=true.
     */
    'test_mode' => env('ADMOB_TEST_MODE', env('APP_ENV', 'production') !== 'production'),

    /*
     * Comma-separated device IDs that should always receive test ads, even when
     * test_mode is false. Find your device ID by running once with
     * test_mode=true and looking at the device log.
     */
    'test_devices' => array_values(array_filter(array_map('trim', explode(',', (string) env('ADMOB_TEST_DEVICES', ''))))),

    /*
     * When true, every bridge call (method + params + response) is traced at
     * debug log level via a LoggingBridge decorator. Leave off in production.
     */
    'debug' => env('ADMOB_DEBUG', false),

    /*
     * JavaScript API. When true, the plugin registers POST {js_api_prefix}/call,
     * a thin same-origin endpoint the shipped resources/js/admob.js module (and
     * the <admob-banner> Web Component) call. Requests run the Admob facade, so
     * slot resolution, the consent gate, frequency caps, and the enabled
     * kill-switch all apply server-side. Set false to omit the route entirely.
     */
    'js_api' => env('ADMOB_JS_API', true),
    'js_api_prefix' => '_admob',

    /*
     * Built-in, self-contained AdMob test/debug page. When enabled the plugin
     * serves a generic HTML page at `test_route` that exercises every format +
     * consent flow and shows a live event log - handy for verifying an
     * integration. Defaults on outside production (like test_mode); set
     * NATIVEPHP_START_URL to the route to boot straight into it.
     */
    'test_page' => env('ADMOB_TEST_PAGE', env('APP_ENV', 'production') !== 'production'),
    'test_route' => env('ADMOB_TEST_ROUTE', '_admob/test'),

    'consent' => [
        /*
         * EU/UK User Messaging Platform consent form. Required for serving
         * personalised ads in EEA + UK. Set false only if your audience is
         * entirely outside those regions.
         */
        'ump_enabled' => env('ADMOB_UMP_ENABLED', true),

        /*
         * iOS App Tracking Transparency prompt. Required by App Store review if
         * you collect IDFA. Set false if you only serve non-personalised ads on
         * iOS.
         */
        'att_enabled' => env('ADMOB_ATT_ENABLED', true),

        /*
         * UMP debug geography for testing the consent form outside the EEA. One
         * of EEA, NOT_EEA, DISABLED. The native layer reads
         * ADMOB_UMP_DEBUG_GEOGRAPHY directly from the process env (like
         * ADMOB_TEST_DEVICES); this key exists for discoverability and parity.
         */
        'ump_debug_geography' => env('ADMOB_UMP_DEBUG_GEOGRAPHY', 'DISABLED'),
    ],

    /*
     * Named slots resolved by Admob::banner('slot'), ::interstitial('slot'),
     * etc. Slots are entirely app-defined: you choose the names and where their
     * ad unit IDs come from. The package has NO env-key convention - the
     * env(...) calls below are illustrative only. Slots are resolved solely from
     * config('admob.slots.{format}.{name}').
     *
     * Each slot value is either a plain ad unit ID string (ca-app-pub-XXXX/YYYY,
     * universal / single-platform) OR a platform-keyed array for cross-platform
     * apps, where the running platform is selected automatically:
     *
     *   'home_footer' => [
     *       'android' => env('ADMOB_BANNER_HOME_FOOTER_ANDROID'),
     *       'ios'     => env('ADMOB_BANNER_HOME_FOOTER_IOS'),
     *   ],
     *
     * Defaults are empty.
     */
    'slots' => [
        'banner' => [
            // 'home_footer' => env('ADMOB_BANNER_HOME_FOOTER'),
        ],
        'interstitial' => [
            // 'level_complete' => env('ADMOB_INTERSTITIAL_LEVEL_COMPLETE'),
        ],
        'rewarded' => [
            // 'unlock_feature' => env('ADMOB_REWARDED_UNLOCK_FEATURE'),
        ],
        'rewarded_interstitial' => [
            // 'session_break' => env('ADMOB_REWARDED_INTERSTITIAL_SESSION_BREAK'),
        ],
        'app_open' => [
            // 'cold_start' => env('ADMOB_APPOPEN_COLD_START'),
        ],
    ],

    /*
     * Banner-specific behaviour for the <x-admob::banner> Blade component. The
     * native banner is a screen overlay that survives WebView navigation, so the
     * component tears it down by listening for these DOM events (on BOTH window
     * and document) and calling Admob.HideBanner. Defaults cover the common
     * runtimes: Livewire SPA nav, Inertia visits, and a full-page-unload
     * fallback. Auto-hide needs SOME navigation event from your host app -
     * override this list for a different router, or set [] to disable and call
     * ->hide() yourself. Inertia/Vue/React SPAs should prefer the JS API +
     * component lifecycle (the <admob-banner> Web Component) instead.
     */
    'banner' => [
        'hide_on_events' => ['livewire:navigating', 'inertia:before', 'pagehide'],

        /*
         * Extra gap (density-independent pixels) between the banner and its
         * screen edge, per position. The banner is a native overlay pinned to
         * the top or bottom edge, so use this to clear chrome it would otherwise
         * sit on top of - most commonly a native bottom-nav. iOS already insets
         * for the system safe area; this adds on top of that. Override per call
         * with ->show($position, $offset) or <admob-banner offset="...">.
         */
        'offset' => [
            'top' => (int) env('ADMOB_BANNER_OFFSET_TOP', 0),
            'bottom' => (int) env('ADMOB_BANNER_OFFSET_BOTTOM', 0),
        ],

        /*
         * Automatically inset the banner past the OS system bars (status bar at
         * top, navigation/gesture bar at bottom) so it isn't clipped behind
         * them. iOS already does this via the safe-area guide; this brings
         * Android in line. The `offset` above stacks on top of this. Set false
         * for a true edge-to-edge banner against the raw screen edge.
         */
        'safe_area' => (bool) env('ADMOB_BANNER_SAFE_AREA', true),
    ],

    /*
     * Per-format show throttling for the full-screen formats (interstitial,
     * rewarded, rewarded_interstitial, app_open). Both constraints are opt-in:
     * 0 or omitted means no limit. Per-slot entries under 'slots' override the
     * per-format defaults. Banner is exempt (it's a persistent overlay).
     * test_mode bypasses all caps. Persisted in the cache so caps survive
     * relaunches.
     */
    'frequency' => [
        // 'interstitial' => ['min_interval_seconds' => 60, 'max_per_day' => 10],
        // 'rewarded_interstitial' => ['min_interval_seconds' => 120],
        // 'slots' => [
        //     'interstitial' => ['level_complete' => ['min_interval_seconds' => 30]],
        // ],
    ],

    // Cache store used to persist frequency-cap counters. null = default store.
    'frequency_store' => env('ADMOB_FREQUENCY_STORE', null),

];

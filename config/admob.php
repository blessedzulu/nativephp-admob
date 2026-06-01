<?php

return [

    // Master switch. Even with this true, ads only show after consent (UMP)
    // and tracking authorization (ATT, iOS) have been resolved.
    'enabled' => env('ADMOB_ENABLED', false),

    // Your AdMob app ID. Different shape to AdSense's ca-pub-XXXX:
    // AdMob uses ca-app-pub-XXXX~YYYY. One per platform if you maintain
    // separate iOS / Android apps; set the active one in .env per build.
    'app_id' => env('ADMOB_APP_ID', ''),

    // When true, the SDK is initialised with Google's reserved test ad units
    // regardless of the slot IDs below. Auto-on in non-production, opt-in
    // anywhere else via ADMOB_TEST_MODE=true.
    'test_mode' => env('ADMOB_TEST_MODE', env('APP_ENV', 'production') !== 'production'),

    // Comma-separated device IDs that should always receive test ads, even
    // when test_mode is false. Find your device ID by running once with
    // test_mode=true and looking at the device log.
    'test_devices' => array_values(array_filter(array_map('trim', explode(',', (string) env('ADMOB_TEST_DEVICES', ''))))),

    'consent' => [
        // EU/UK User Messaging Platform consent form. Required for serving
        // personalised ads in EEA + UK. Set false only if your audience is
        // entirely outside those regions.
        'ump_enabled' => env('ADMOB_UMP_ENABLED', true),

        // iOS App Tracking Transparency prompt. Required by App Store review
        // if you collect IDFA. Set false if you only serve non-personalised
        // ads on iOS.
        'att_enabled' => env('ADMOB_ATT_ENABLED', true),

        // UMP debug geography for testing the consent form outside the EEA.
        // One of EEA, NOT_EEA, DISABLED. The native layer reads
        // ADMOB_UMP_DEBUG_GEOGRAPHY directly from the process env (like
        // ADMOB_TEST_DEVICES); this key exists for discoverability and parity.
        'ump_debug_geography' => env('ADMOB_UMP_DEBUG_GEOGRAPHY', 'DISABLED'),
    ],

    // Named slots resolved by Admob::banner('slot'), ::interstitial('slot'),
    // etc. Slot names are app-defined; keep them human-readable. Each value
    // is the AdMob ad unit ID for that placement (ca-app-pub-XXXX/YYYY).
    'slots' => [
        'banner' => [
            // 'calculator_bottom' => env('ADMOB_BANNER_CALCULATOR_BOTTOM'),
        ],
        'interstitial' => [
            // 'between_calculations' => env('ADMOB_INTERSTITIAL_BETWEEN_CALCULATIONS'),
        ],
        'rewarded' => [
            // 'export_pdf' => env('ADMOB_REWARDED_EXPORT_PDF'),
        ],
        'rewarded_interstitial' => [
            // 'chapter_break' => env('ADMOB_REWARDED_INTERSTITIAL_CHAPTER_BREAK'),
        ],
        'app_open' => [
            // 'cold_start' => env('ADMOB_APPOPEN_COLD_START'),
        ],
    ],

];

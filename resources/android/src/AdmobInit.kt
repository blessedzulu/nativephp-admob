package com.blessedzulu.nativephp.admob

import android.content.Context
import com.google.android.gms.ads.MobileAds

/**
 * Boots the Google Mobile Ads SDK once at app startup. Registered as the
 * Android init_function in nativephp.json so it runs before any bridge
 * function becomes available.
 *
 * The completion callback is intentionally empty - the SDK already logs
 * adapter initialisation status to logcat.
 *
 * Test devices are managed in the AdMob console (Settings -> Test devices)
 * by raw advertising ID - one source of truth, no baked-in IDs to go stale.
 */
object AdmobInit {
    @JvmStatic
    fun initialize(context: Context) {
        MobileAds.initialize(context) { /* status callback - logged by SDK */ }

        BannerLifecycle.register()
        AppOpenLifecycle.register()
    }
}

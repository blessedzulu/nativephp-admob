package com.blessedzulu.nativephp.admob

import android.content.Context
import com.google.android.gms.ads.MobileAds
import com.google.android.gms.ads.RequestConfiguration

/**
 * Boots the Google Mobile Ads SDK once at app startup. Registered as the
 * Android init_function in nativephp.json so it runs before any bridge
 * function becomes available.
 *
 * The completion callback is intentionally empty - the SDK already logs
 * adapter initialisation status to logcat. If a consumer needs the status,
 * they listen for the ConsentChanged Laravel event after Admob::start().
 */
object AdmobInit {
    @JvmStatic
    fun initialize(context: Context) {
        MobileAds.initialize(context) { /* status callback - logged by SDK */ }

        System.getenv("ADMOB_TEST_DEVICES")
            ?.split(",")
            ?.map { it.trim() }
            ?.filter { it.isNotBlank() }
            ?.takeIf { it.isNotEmpty() }
            ?.let { testIds ->
                MobileAds.setRequestConfiguration(
                    RequestConfiguration.Builder()
                        .setTestDeviceIds(testIds)
                        .build()
                )
            }
    }
}

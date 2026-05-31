package com.blessedzulu.nativephp.admob

import com.google.android.gms.ads.interstitial.InterstitialAd

/**
 * Slot-keyed registry for loaded InterstitialAd instances.
 *
 * Interstitials are one-shot: each loaded ad survives until it is shown
 * (and dismissed) or fails to show. The registry holds the ad between
 * load() and show() and is cleared on dismissal or failure.
 *
 * Thread-safety: all public methods are synchronized. Callers should still
 * post() to the main thread before invoking InterstitialAd APIs since
 * Google's SDK requires it.
 */
object InterstitialRegistry {
    private val ads = mutableMapOf<String, InterstitialAd>()

    @Synchronized
    fun put(slot: String, ad: InterstitialAd) {
        ads[slot] = ad
    }

    @Synchronized
    fun get(slot: String): InterstitialAd? = ads[slot]

    @Synchronized
    fun remove(slot: String): InterstitialAd? = ads.remove(slot)

    @Synchronized
    fun clear() {
        ads.clear()
    }
}

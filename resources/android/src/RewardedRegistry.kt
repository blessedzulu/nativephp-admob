package com.blessedzulu.nativephp.admob

import com.google.android.gms.ads.rewarded.RewardedAd

/**
 * Slot-keyed registry for loaded RewardedAd instances.
 *
 * Same one-shot semantics as InterstitialRegistry: each loaded ad survives
 * until shown (and dismissed, with or without earning a reward) or failed
 * to show. The slot is cleared in those terminal callbacks.
 *
 * Thread-safety: all public methods are synchronized. Callers should still
 * post() to the main thread before invoking RewardedAd APIs since Google's
 * SDK requires it.
 */
object RewardedRegistry {
    private val ads = mutableMapOf<String, RewardedAd>()

    @Synchronized
    fun put(slot: String, ad: RewardedAd) {
        ads[slot] = ad
    }

    @Synchronized
    fun get(slot: String): RewardedAd? = ads[slot]

    @Synchronized
    fun remove(slot: String): RewardedAd? = ads.remove(slot)

    @Synchronized
    fun clear() {
        ads.clear()
    }
}

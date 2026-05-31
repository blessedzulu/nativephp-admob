package com.blessedzulu.nativephp.admob

import com.google.android.gms.ads.rewardedinterstitial.RewardedInterstitialAd

/**
 * Slot-keyed registry for loaded RewardedInterstitialAd instances.
 *
 * RewardedInterstitial differs from RewardedAd in that the user is shown
 * a 5-second skip warning rather than an opt-in entry screen. Reward
 * eligibility still depends on the user not skipping. Same one-shot
 * semantics: cleared in dismissal or failed-show callbacks.
 *
 * Thread-safety: all public methods are synchronized. Callers should still
 * post() to the main thread before invoking the SDK.
 */
object RewardedInterstitialRegistry {
    private val ads = mutableMapOf<String, RewardedInterstitialAd>()

    @Synchronized
    fun put(slot: String, ad: RewardedInterstitialAd) {
        ads[slot] = ad
    }

    @Synchronized
    fun get(slot: String): RewardedInterstitialAd? = ads[slot]

    @Synchronized
    fun remove(slot: String): RewardedInterstitialAd? = ads.remove(slot)

    @Synchronized
    fun clear() {
        ads.clear()
    }
}

package com.blessedzulu.nativephp.admob

/**
 * Cross-format state for full-screen ad presentations.
 *
 * Solves the problem where dismissing an interstitial/rewarded/rewarded-
 * interstitial/app-open ad triggers MainActivity.onResume, which would
 * otherwise cause AppOpenLifecycle to auto-show the cached App Open ad
 * immediately. Every fullscreen FullScreenContentCallback updates
 * `lastDismissedAt` on dismissal; AppOpenLifecycle's onResume callback
 * suppresses auto-show if the dismissal was within the grace window.
 *
 * The grace window is intentionally generous (1500 ms) - the resume event
 * follows the dismiss callback by tens to a few hundred ms in practice; a
 * full second of cushion absorbs slower devices and emulator scheduling
 * jitter.
 */
object FullScreenAdState {
    const val DISMISS_GRACE_MS = 1500L

    @Volatile
    var lastDismissedAt: Long = 0L

    fun markDismissed() {
        lastDismissedAt = System.currentTimeMillis()
    }

    fun recentlyDismissed(): Boolean {
        val last = lastDismissedAt
        if (last == 0L) return false
        return (System.currentTimeMillis() - last) < DISMISS_GRACE_MS
    }
}

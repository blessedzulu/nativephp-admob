package com.blessedzulu.nativephp.admob

import com.google.android.gms.ads.appopen.AppOpenAd

/**
 * Slot-keyed registry for loaded AppOpenAd instances plus their load
 * timestamps for staleness checks.
 *
 * Google strongly recommends discarding app-open ads older than 4 hours -
 * stale ones drift attribution and serve poorly. `isFresh(slot)` enforces
 * this threshold.
 *
 * One-shot: the slot is cleared in dismissal callbacks. The AppOpenLifecycle
 * observer also clears stale slots so the next lifecycle resume finds them
 * empty and either does nothing or triggers a fresh load.
 *
 * Thread-safety: all public methods are synchronized.
 */
object AppOpenRegistry {
    private val ads = mutableMapOf<String, AppOpenAd>()
    private val loadTimes = mutableMapOf<String, Long>()

    private const val STALE_THRESHOLD_MS = 4L * 60L * 60L * 1000L

    @Synchronized
    fun put(slot: String, ad: AppOpenAd) {
        ads[slot] = ad
        loadTimes[slot] = System.currentTimeMillis()
    }

    @Synchronized
    fun get(slot: String): AppOpenAd? = ads[slot]

    @Synchronized
    fun remove(slot: String): AppOpenAd? {
        loadTimes.remove(slot)
        return ads.remove(slot)
    }

    @Synchronized
    fun isFresh(slot: String): Boolean {
        val t = loadTimes[slot] ?: return false
        return (System.currentTimeMillis() - t) < STALE_THRESHOLD_MS
    }

    @Synchronized
    fun ageMs(slot: String): Long? = loadTimes[slot]?.let { System.currentTimeMillis() - it }

    @Synchronized
    fun allSlots(): List<String> = ads.keys.toList()

    @Synchronized
    fun clear() {
        ads.clear()
        loadTimes.clear()
    }
}

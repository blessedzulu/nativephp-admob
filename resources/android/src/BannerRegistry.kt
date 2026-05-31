package com.blessedzulu.nativephp.admob

import android.widget.FrameLayout
import com.google.android.gms.ads.AdView

/**
 * Slot-keyed registry for banner ad views and their attachment containers.
 *
 * Holds two parallel maps so we can:
 *   - cache loaded AdView objects between load() and show() calls
 *   - track the FrameLayout container we wrap each AdView in so hide() can
 *     remove the right one from the activity's content view
 *
 * Thread-safety: All public methods are synchronized. Callers should still
 * post() to the main thread before invoking, since the underlying AdView
 * operations require it.
 */
object BannerRegistry {
    private val ads = mutableMapOf<String, AdView>()
    private val containers = mutableMapOf<String, FrameLayout>()

    @Synchronized
    fun put(slot: String, adView: AdView) {
        ads[slot] = adView
    }

    @Synchronized
    fun get(slot: String): AdView? = ads[slot]

    @Synchronized
    fun putContainer(slot: String, container: FrameLayout) {
        containers[slot] = container
    }

    @Synchronized
    fun getContainer(slot: String): FrameLayout? = containers[slot]

    @Synchronized
    fun removeContainer(slot: String): FrameLayout? = containers.remove(slot)

    @Synchronized
    fun remove(slot: String) {
        containers.remove(slot)?.let { container ->
            (container.parent as? android.view.ViewGroup)?.removeView(container)
        }
        ads.remove(slot)?.destroy()
    }

    @Synchronized
    fun all(): List<AdView> = ads.values.toList()

    @Synchronized
    fun clear() {
        ads.values.forEach { it.destroy() }
        containers.values.forEach { container ->
            (container.parent as? android.view.ViewGroup)?.removeView(container)
        }
        ads.clear()
        containers.clear()
    }
}

package com.blessedzulu.nativephp.admob

import androidx.fragment.app.FragmentActivity
import com.google.android.gms.ads.AdError
import com.google.android.gms.ads.FullScreenContentCallback
import com.nativephp.mobile.lifecycle.NativePHPLifecycle
import com.nativephp.mobile.utils.NativeActionCoordinator
import org.json.JSONObject
import java.lang.ref.WeakReference

/**
 * Auto-show app-open ads when the app foregrounds, after skipping the first
 * resume (which is the cold-start splash). Honours the 4-hour staleness rule
 * via AppOpenRegistry.isFresh().
 *
 * Stale ads are silently discarded; a fresh load() is NOT kicked off here -
 * that's the consumer's job. Documented as a known limitation.
 *
 * Registered once at app boot from AdmobInit.initialize(). The current
 * activity reference is captured weakly via bindActivity() during the
 * LoadAppOpen bridge call.
 */
object AppOpenLifecycle {
    private var registered = false
    // NativePHP's init_function runs AFTER MainActivity.onResume on first
    // launch, so by the time our observer subscribes the cold-start resume
    // has already fired. Default to "consumed" so the first observed resume
    // (background -> foreground) triggers auto-show. If no ad is loaded yet,
    // the show path silently dispatches AdFailedToShow=no_loaded_ad.
    private var coldStartConsumed = true
    private var activityRef: WeakReference<FragmentActivity>? = null

    private const val EVENT_BASE = "BlessedZulu\\NativePhpAdmob\\Events"

    @JvmStatic
    fun bindActivity(activity: FragmentActivity) {
        activityRef = WeakReference(activity)
    }

    @JvmStatic
    @Synchronized
    fun register() {
        if (registered) return
        registered = true

        NativePHPLifecycle.on("onResume") {
            if (!coldStartConsumed) {
                coldStartConsumed = true
                return@on
            }
            val activity = activityRef?.get() ?: return@on
            activity.runOnUiThread {
                AppOpenRegistry.allSlots().forEach { slot ->
                    val ad = AppOpenRegistry.get(slot) ?: return@forEach
                    if (!AppOpenRegistry.isFresh(slot)) {
                        AppOpenRegistry.remove(slot)
                        return@forEach
                    }
                    attachCallback(activity, slot, ad)
                    ad.show(activity)
                }
            }
        }
    }

    /**
     * Shared FullScreenContentCallback wiring used by both ShowAppOpen
     * (manual override) and the auto-show lifecycle path. Dispatches the
     * five standard lifecycle events with format=app_open. On dismissal or
     * failed-show, clears the registry slot.
     */
    @JvmStatic
    fun attachCallback(activity: FragmentActivity, slot: String, ad: com.google.android.gms.ads.appopen.AppOpenAd) {
        ad.fullScreenContentCallback = object : FullScreenContentCallback() {
            override fun onAdShowedFullScreenContent() {
                dispatch(activity, "AdShown", mapOf("slot" to slot, "format" to "app_open"))
            }

            override fun onAdDismissedFullScreenContent() {
                AppOpenRegistry.remove(slot)
                dispatch(activity, "AdDismissed", mapOf("slot" to slot, "format" to "app_open"))
            }

            override fun onAdFailedToShowFullScreenContent(error: AdError) {
                AppOpenRegistry.remove(slot)
                dispatch(
                    activity,
                    "AdFailedToShow",
                    mapOf(
                        "slot" to slot,
                        "format" to "app_open",
                        "errorCode" to error.code,
                        "errorMessage" to error.message,
                    ),
                )
            }

            override fun onAdImpression() {
                dispatch(activity, "AdImpression", mapOf("slot" to slot, "format" to "app_open"))
            }

            override fun onAdClicked() {
                dispatch(activity, "AdClicked", mapOf("slot" to slot, "format" to "app_open"))
            }
        }
    }

    private fun dispatch(activity: FragmentActivity, eventClass: String, payload: Map<String, Any>) {
        activity.runOnUiThread {
            try {
                NativeActionCoordinator.dispatchEvent(
                    activity,
                    "$EVENT_BASE\\$eventClass",
                    JSONObject(payload).toString(),
                )
            } catch (_: Throwable) {
                // swallowed; lifecycle observer must never crash the app
            }
        }
    }
}

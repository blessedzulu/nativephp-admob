package com.blessedzulu.nativephp.admob

import android.os.Handler
import android.os.Looper
import android.util.Log
import android.view.Gravity
import android.view.ViewGroup
import android.widget.FrameLayout
import androidx.fragment.app.FragmentActivity
import com.google.android.gms.ads.AdListener
import com.google.android.gms.ads.AdRequest
import com.google.android.gms.ads.AdSize
import com.google.android.gms.ads.AdView
import com.google.android.gms.ads.LoadAdError
import com.nativephp.mobile.bridge.BridgeFunction
import com.nativephp.mobile.utils.NativeActionCoordinator
import org.json.JSONObject

/**
 * AdMob bridge function implementations.
 *
 * Every class takes a FragmentActivity in its primary constructor because
 * NativePHP's AndroidPluginCompiler emits `ClassName(activity)` when
 * `params` is omitted from the manifest entry (the default).
 *
 * Phase 3 ships real implementations for the three banner functions
 * (LoadBanner / ShowBanner / HideBanner). Every other function still
 * returns a "not implemented" error - those land in Phases 4-8.
 *
 * `Platform` is the one always-real exception so PHP-side `Att` can
 * short-circuit on Android without round-tripping a fake error.
 */
object AdmobFunctions {
    private const val TAG = "AdmobFunctions"
    private val mainHandler = Handler(Looper.getMainLooper())
    private const val EVENT_BASE = "BlessedZulu\\NativePhpAdmob\\Events"

    private fun notImplemented(name: String): Map<String, Any> =
        mapOf("success" to false, "error" to "$name not implemented in v0.4.x.")

    private fun success(data: Any? = null): Map<String, Any> {
        val result = mutableMapOf<String, Any>("success" to true)
        if (data != null) result["data"] = data
        return result
    }

    private fun failure(message: String): Map<String, Any> =
        mapOf("success" to false, "error" to message)

    private fun runOnUiThread(block: () -> Unit) {
        if (Looper.myLooper() == Looper.getMainLooper()) block()
        else mainHandler.post { block() }
    }

    private fun dispatchEvent(activity: FragmentActivity, eventClass: String, payload: Map<String, Any>) {
        runOnUiThread {
            try {
                NativeActionCoordinator.dispatchEvent(
                    activity,
                    "$EVENT_BASE\\$eventClass",
                    JSONObject(payload).toString()
                )
            } catch (e: Exception) {
                Log.e(TAG, "❌ Failed to dispatch $eventClass: ${e.message}", e)
            }
        }
    }

    private fun adaptiveBannerSize(activity: FragmentActivity): AdSize {
        val display = activity.resources.displayMetrics
        val widthDp = (display.widthPixels / display.density).toInt()
        return AdSize.getCurrentOrientationAnchoredAdaptiveBannerAdSize(activity, widthDp)
    }

    // ---------- Real implementations: banner ----------

    class LoadBanner(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val slot = parameters["slot"] as? String ?: return notImplemented("LoadBanner: slot missing")
            val unitId = parameters["unit_id"] as? String ?: return notImplemented("LoadBanner: unit_id missing")

            runOnUiThread {
                val existing = BannerRegistry.get(slot)
                val adView = existing ?: AdView(activity).also { newAdView ->
                    newAdView.setAdSize(adaptiveBannerSize(activity))
                    newAdView.adUnitId = unitId
                    newAdView.adListener = object : AdListener() {
                        override fun onAdLoaded() {
                            dispatchEvent(activity, "AdLoaded", mapOf("slot" to slot, "format" to "banner"))
                        }

                        override fun onAdFailedToLoad(error: LoadAdError) {
                            dispatchEvent(activity, "AdFailedToLoad", mapOf(
                                "slot" to slot,
                                "format" to "banner",
                                "errorCode" to error.code,
                                "errorMessage" to error.message,
                            ))
                        }

                        override fun onAdImpression() {
                            dispatchEvent(activity, "AdImpression", mapOf("slot" to slot, "format" to "banner"))
                        }

                        override fun onAdClicked() {
                            dispatchEvent(activity, "AdClicked", mapOf("slot" to slot, "format" to "banner"))
                        }
                    }
                    BannerRegistry.put(slot, newAdView)
                }

                adView.loadAd(AdRequest.Builder().build())
            }

            return success()
        }
    }

    class ShowBanner(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val slot = parameters["slot"] as? String ?: return notImplemented("ShowBanner: slot missing")
            val position = (parameters["position"] as? String) ?: "bottom"

            runOnUiThread {
                val adView = BannerRegistry.get(slot) ?: run {
                    dispatchEvent(
                        activity,
                        "AdFailedToShow",
                        mapOf("slot" to slot, "format" to "banner", "error" to "no_loaded_ad"),
                    )
                    return@runOnUiThread
                }

                BannerRegistry.removeContainer(slot)?.let { existing ->
                    (existing.parent as? ViewGroup)?.removeView(existing)
                }

                (adView.parent as? ViewGroup)?.removeView(adView)

                val container = FrameLayout(activity)
                val containerParams = FrameLayout.LayoutParams(
                    FrameLayout.LayoutParams.MATCH_PARENT,
                    FrameLayout.LayoutParams.WRAP_CONTENT,
                ).apply {
                    gravity = if (position == "top") Gravity.TOP else Gravity.BOTTOM
                }

                val adViewParams = FrameLayout.LayoutParams(
                    FrameLayout.LayoutParams.MATCH_PARENT,
                    FrameLayout.LayoutParams.WRAP_CONTENT,
                ).apply {
                    gravity = if (position == "top") Gravity.TOP else Gravity.BOTTOM
                }
                container.addView(adView, adViewParams)

                val decorContent = activity.findViewById<ViewGroup>(android.R.id.content)
                decorContent?.addView(container, containerParams)
                BannerRegistry.putContainer(slot, container)

                dispatchEvent(activity, "AdShown", mapOf("slot" to slot, "format" to "banner"))
            }

            return success()
        }
    }

    class HideBanner(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val slot = parameters["slot"] as? String ?: return notImplemented("HideBanner: slot missing")

            runOnUiThread {
                BannerRegistry.removeContainer(slot)?.let { container ->
                    (container.parent as? ViewGroup)?.removeView(container)
                }
            }

            return success()
        }
    }

    // ---------- Always-real: platform identifier ----------

    class Platform(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> =
            success(mapOf("platform" to "android"))
    }

    // ---------- Stubs: land in later phases ----------

    class Start(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> = notImplemented("Admob.Start")
    }

    class LoadInterstitial(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> = notImplemented("Admob.LoadInterstitial")
    }

    class InterstitialReady(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> = notImplemented("Admob.InterstitialReady")
    }

    class ShowInterstitial(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> = notImplemented("Admob.ShowInterstitial")
    }

    class LoadRewarded(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> = notImplemented("Admob.LoadRewarded")
    }

    class RewardedReady(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> = notImplemented("Admob.RewardedReady")
    }

    class ShowRewarded(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> = notImplemented("Admob.ShowRewarded")
    }

    class LoadRewardedInterstitial(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> = notImplemented("Admob.LoadRewardedInterstitial")
    }

    class RewardedInterstitialReady(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> = notImplemented("Admob.RewardedInterstitialReady")
    }

    class ShowRewardedInterstitial(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> = notImplemented("Admob.ShowRewardedInterstitial")
    }

    class LoadAppOpen(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> = notImplemented("Admob.LoadAppOpen")
    }

    class UmpRequestInfo(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> = notImplemented("Admob.UmpRequestInfo")
    }

    class UmpShowForm(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> = notImplemented("Admob.UmpShowForm")
    }

    class UmpCanRequestAds(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> = notImplemented("Admob.UmpCanRequestAds")
    }

    class UmpStatus(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> = notImplemented("Admob.UmpStatus")
    }

    class UmpReset(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> = notImplemented("Admob.UmpReset")
    }

    class AttRequest(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> = notImplemented("Admob.AttRequest")
    }

    class AttStatus(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> = notImplemented("Admob.AttStatus")
    }
}

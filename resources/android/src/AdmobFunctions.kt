package com.blessedzulu.nativephp.admob

import android.os.Handler
import android.os.Looper
import android.util.Log
import android.view.Gravity
import android.view.ViewGroup
import android.widget.FrameLayout
import androidx.fragment.app.FragmentActivity
import com.google.android.gms.ads.AdError
import com.google.android.gms.ads.AdListener
import com.google.android.gms.ads.AdRequest
import com.google.android.gms.ads.AdSize
import com.google.android.gms.ads.AdView
import com.google.android.gms.ads.FullScreenContentCallback
import com.google.android.gms.ads.LoadAdError
import com.google.android.gms.ads.OnUserEarnedRewardListener
import com.google.android.gms.ads.interstitial.InterstitialAd
import com.google.android.gms.ads.interstitial.InterstitialAdLoadCallback
import com.google.android.gms.ads.rewarded.RewardedAd
import com.google.android.gms.ads.rewarded.RewardedAdLoadCallback
import com.google.android.gms.ads.rewardedinterstitial.RewardedInterstitialAd
import com.google.android.gms.ads.rewardedinterstitial.RewardedInterstitialAdLoadCallback
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
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val slot = parameters["slot"] as? String ?: return notImplemented("LoadInterstitial: slot missing")
            val unitId = parameters["unit_id"] as? String ?: return notImplemented("LoadInterstitial: unit_id missing")

            runOnUiThread {
                InterstitialAd.load(
                    activity,
                    unitId,
                    AdRequest.Builder().build(),
                    object : InterstitialAdLoadCallback() {
                        override fun onAdLoaded(ad: InterstitialAd) {
                            InterstitialRegistry.put(slot, ad)
                            dispatchEvent(activity, "AdLoaded", mapOf("slot" to slot, "format" to "interstitial"))
                        }

                        override fun onAdFailedToLoad(error: LoadAdError) {
                            InterstitialRegistry.remove(slot)
                            dispatchEvent(
                                activity,
                                "AdFailedToLoad",
                                mapOf(
                                    "slot" to slot,
                                    "format" to "interstitial",
                                    "errorCode" to error.code,
                                    "errorMessage" to error.message,
                                ),
                            )
                        }
                    },
                )
            }

            return success()
        }
    }

    class InterstitialReady(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val slot = parameters["slot"] as? String ?: return notImplemented("InterstitialReady: slot missing")
            return success(mapOf("ready" to (InterstitialRegistry.get(slot) != null)))
        }
    }

    class ShowInterstitial(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val slot = parameters["slot"] as? String ?: return notImplemented("ShowInterstitial: slot missing")

            runOnUiThread {
                val ad = InterstitialRegistry.get(slot) ?: run {
                    dispatchEvent(
                        activity,
                        "AdFailedToShow",
                        mapOf("slot" to slot, "format" to "interstitial", "error" to "no_loaded_ad"),
                    )
                    return@runOnUiThread
                }

                ad.fullScreenContentCallback = object : FullScreenContentCallback() {
                    override fun onAdShowedFullScreenContent() {
                        dispatchEvent(activity, "AdShown", mapOf("slot" to slot, "format" to "interstitial"))
                    }

                    override fun onAdDismissedFullScreenContent() {
                        InterstitialRegistry.remove(slot)
                        dispatchEvent(activity, "AdDismissed", mapOf("slot" to slot, "format" to "interstitial"))
                    }

                    override fun onAdFailedToShowFullScreenContent(error: AdError) {
                        InterstitialRegistry.remove(slot)
                        dispatchEvent(
                            activity,
                            "AdFailedToShow",
                            mapOf(
                                "slot" to slot,
                                "format" to "interstitial",
                                "errorCode" to error.code,
                                "errorMessage" to error.message,
                            ),
                        )
                    }

                    override fun onAdImpression() {
                        dispatchEvent(activity, "AdImpression", mapOf("slot" to slot, "format" to "interstitial"))
                    }

                    override fun onAdClicked() {
                        dispatchEvent(activity, "AdClicked", mapOf("slot" to slot, "format" to "interstitial"))
                    }
                }

                ad.show(activity)
            }

            return success()
        }
    }

    class LoadRewarded(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val slot = parameters["slot"] as? String ?: return notImplemented("LoadRewarded: slot missing")
            val unitId = parameters["unit_id"] as? String ?: return notImplemented("LoadRewarded: unit_id missing")

            runOnUiThread {
                RewardedAd.load(
                    activity,
                    unitId,
                    AdRequest.Builder().build(),
                    object : RewardedAdLoadCallback() {
                        override fun onAdLoaded(ad: RewardedAd) {
                            RewardedRegistry.put(slot, ad)
                            dispatchEvent(activity, "AdLoaded", mapOf("slot" to slot, "format" to "rewarded"))
                        }

                        override fun onAdFailedToLoad(error: LoadAdError) {
                            RewardedRegistry.remove(slot)
                            dispatchEvent(
                                activity,
                                "AdFailedToLoad",
                                mapOf(
                                    "slot" to slot,
                                    "format" to "rewarded",
                                    "errorCode" to error.code,
                                    "errorMessage" to error.message,
                                ),
                            )
                        }
                    },
                )
            }

            return success()
        }
    }

    class RewardedReady(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val slot = parameters["slot"] as? String ?: return notImplemented("RewardedReady: slot missing")
            return success(mapOf("ready" to (RewardedRegistry.get(slot) != null)))
        }
    }

    class ShowRewarded(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val slot = parameters["slot"] as? String ?: return notImplemented("ShowRewarded: slot missing")

            runOnUiThread {
                val ad = RewardedRegistry.get(slot) ?: run {
                    dispatchEvent(
                        activity,
                        "AdFailedToShow",
                        mapOf("slot" to slot, "format" to "rewarded", "error" to "no_loaded_ad"),
                    )
                    return@runOnUiThread
                }

                ad.fullScreenContentCallback = object : FullScreenContentCallback() {
                    override fun onAdShowedFullScreenContent() {
                        dispatchEvent(activity, "AdShown", mapOf("slot" to slot, "format" to "rewarded"))
                    }

                    override fun onAdDismissedFullScreenContent() {
                        RewardedRegistry.remove(slot)
                        dispatchEvent(activity, "AdDismissed", mapOf("slot" to slot, "format" to "rewarded"))
                    }

                    override fun onAdFailedToShowFullScreenContent(error: AdError) {
                        RewardedRegistry.remove(slot)
                        dispatchEvent(
                            activity,
                            "AdFailedToShow",
                            mapOf(
                                "slot" to slot,
                                "format" to "rewarded",
                                "errorCode" to error.code,
                                "errorMessage" to error.message,
                            ),
                        )
                    }

                    override fun onAdImpression() {
                        dispatchEvent(activity, "AdImpression", mapOf("slot" to slot, "format" to "rewarded"))
                    }

                    override fun onAdClicked() {
                        dispatchEvent(activity, "AdClicked", mapOf("slot" to slot, "format" to "rewarded"))
                    }
                }

                ad.show(activity, OnUserEarnedRewardListener { rewardItem ->
                    dispatchEvent(
                        activity,
                        "UserEarnedReward",
                        mapOf(
                            "slot" to slot,
                            "format" to "rewarded",
                            "type" to rewardItem.type,
                            "amount" to rewardItem.amount,
                        ),
                    )
                })
            }

            return success()
        }
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

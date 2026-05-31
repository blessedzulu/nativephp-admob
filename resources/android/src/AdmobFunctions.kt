package com.blessedzulu.nativephp.admob

import com.nativephp.mobile.bridge.BridgeFunction
import com.nativephp.mobile.bridge.BridgeResponse

/**
 * Bridge function stubs for Phase 2 (v0.3.x).
 *
 * Every entry declared in nativephp.json's `bridge_functions` array has a
 * matching class here so that `native:plugin:validate` passes and the build
 * links. Real implementations land in Phase 3 (banner first), Phase 4
 * (UMP), Phase 5 (ATT), etc.
 *
 * `Platform` is the one exception - it returns a real value so the
 * PHP-side `Att` sub-manager can short-circuit on Android without
 * round-tripping a "not implemented" error.
 */
object AdmobFunctions {
    private fun notImplemented(name: String): Map<String, Any?> =
        BridgeResponse.error("$name not implemented in v0.3.x. Bridge function impls land in Phase 3.")

    class Start : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any?> = notImplemented("Admob.Start")
    }

    class Platform : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any?> =
            BridgeResponse.success(mapOf("platform" to "android"))
    }

    class LoadBanner : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any?> = notImplemented("Admob.LoadBanner")
    }

    class ShowBanner : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any?> = notImplemented("Admob.ShowBanner")
    }

    class HideBanner : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any?> = notImplemented("Admob.HideBanner")
    }

    class LoadInterstitial : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any?> = notImplemented("Admob.LoadInterstitial")
    }

    class InterstitialReady : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any?> = notImplemented("Admob.InterstitialReady")
    }

    class ShowInterstitial : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any?> = notImplemented("Admob.ShowInterstitial")
    }

    class LoadRewarded : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any?> = notImplemented("Admob.LoadRewarded")
    }

    class RewardedReady : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any?> = notImplemented("Admob.RewardedReady")
    }

    class ShowRewarded : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any?> = notImplemented("Admob.ShowRewarded")
    }

    class LoadRewardedInterstitial : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any?> = notImplemented("Admob.LoadRewardedInterstitial")
    }

    class RewardedInterstitialReady : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any?> = notImplemented("Admob.RewardedInterstitialReady")
    }

    class ShowRewardedInterstitial : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any?> = notImplemented("Admob.ShowRewardedInterstitial")
    }

    class LoadAppOpen : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any?> = notImplemented("Admob.LoadAppOpen")
    }

    class UmpRequestInfo : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any?> = notImplemented("Admob.UmpRequestInfo")
    }

    class UmpShowForm : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any?> = notImplemented("Admob.UmpShowForm")
    }

    class UmpCanRequestAds : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any?> = notImplemented("Admob.UmpCanRequestAds")
    }

    class UmpStatus : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any?> = notImplemented("Admob.UmpStatus")
    }

    class UmpReset : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any?> = notImplemented("Admob.UmpReset")
    }

    class AttRequest : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any?> = notImplemented("Admob.AttRequest")
    }

    class AttStatus : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any?> = notImplemented("Admob.AttStatus")
    }
}

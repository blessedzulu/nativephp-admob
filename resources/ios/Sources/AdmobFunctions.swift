import Foundation

/**
 * Bridge function stubs for Phase 2 (v0.3.x).
 *
 * Every entry declared in nativephp.json's `bridge_functions` array has a
 * matching class here so that `native:plugin:validate` passes and the build
 * links. Real implementations land in Phase 3 (banner first), Phase 4
 * (UMP), Phase 5 (ATT), etc.
 *
 * `Platform` is the one exception - it returns a real value so the
 * PHP-side `Att` sub-manager can short-circuit appropriately.
 */
enum AdmobFunctions {

    private static func notImplemented(_ name: String) -> [String: Any] {
        return BridgeResponse.error(message: "\(name) not implemented in v0.3.x. Bridge function impls land in Phase 3.")
    }

    class Start: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] { notImplemented("Admob.Start") }
    }

    class Platform: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            return BridgeResponse.success(data: ["platform": "ios"])
        }
    }

    class LoadBanner: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] { notImplemented("Admob.LoadBanner") }
    }

    class ShowBanner: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] { notImplemented("Admob.ShowBanner") }
    }

    class HideBanner: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] { notImplemented("Admob.HideBanner") }
    }

    class LoadInterstitial: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] { notImplemented("Admob.LoadInterstitial") }
    }

    class InterstitialReady: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] { notImplemented("Admob.InterstitialReady") }
    }

    class ShowInterstitial: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] { notImplemented("Admob.ShowInterstitial") }
    }

    class LoadRewarded: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] { notImplemented("Admob.LoadRewarded") }
    }

    class RewardedReady: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] { notImplemented("Admob.RewardedReady") }
    }

    class ShowRewarded: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] { notImplemented("Admob.ShowRewarded") }
    }

    class LoadRewardedInterstitial: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] { notImplemented("Admob.LoadRewardedInterstitial") }
    }

    class RewardedInterstitialReady: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] { notImplemented("Admob.RewardedInterstitialReady") }
    }

    class ShowRewardedInterstitial: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] { notImplemented("Admob.ShowRewardedInterstitial") }
    }

    class LoadAppOpen: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] { notImplemented("Admob.LoadAppOpen") }
    }

    class UmpRequestInfo: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] { notImplemented("Admob.UmpRequestInfo") }
    }

    class UmpShowForm: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] { notImplemented("Admob.UmpShowForm") }
    }

    class UmpCanRequestAds: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] { notImplemented("Admob.UmpCanRequestAds") }
    }

    class UmpStatus: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] { notImplemented("Admob.UmpStatus") }
    }

    class UmpReset: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] { notImplemented("Admob.UmpReset") }
    }

    class AttRequest: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] { notImplemented("Admob.AttRequest") }
    }

    class AttStatus: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] { notImplemented("Admob.AttStatus") }
    }
}

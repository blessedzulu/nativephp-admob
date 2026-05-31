import Foundation
import UIKit
import GoogleMobileAds

/**
 * AdMob bridge function implementations.
 *
 * iOS bridge functions are emitted with no-arg constructors by NativePHP's
 * IOSPluginCompiler (`registry.register("...", function: AdmobFunctions.X())`).
 * Window access happens via `UIApplication.shared.connectedScenes` when
 * needed.
 *
 * Phase 3 ships real implementations for the three banner functions.
 * Every other function still returns a "not implemented" error.
 *
 * `Platform` is the one always-real exception.
 *
 * NOTE: iOS banner implementation ships untested on real iOS hardware.
 * The code follows Google's canonical Swift sample at
 * developers.google.com/admob/ios/banner. Please report issues at
 * https://github.com/blessedzulu/nativephp-admob/issues.
 */
enum AdmobFunctions {

    private static let eventBase = "BlessedZulu\\NativePhpAdmob\\Events"

    private static func notImplemented(_ name: String) -> [String: Any] {
        return ["success": false, "data": NSNull(), "error": "\(name) not implemented in v0.4.x."]
    }

    private static func success(_ data: Any? = nil) -> [String: Any] {
        return ["success": true, "data": data ?? NSNull(), "error": NSNull()]
    }

    private static func keyWindow() -> UIWindow? {
        let windowScenes = UIApplication.shared.connectedScenes.compactMap { $0 as? UIWindowScene }
        let active = windowScenes.first { $0.activationState == .foregroundActive } ?? windowScenes.first
        return active?.windows.first(where: { $0.isKeyWindow }) ?? active?.windows.first
    }

    private static func rootViewController() -> UIViewController? {
        return keyWindow()?.rootViewController
    }

    private static func dispatch(_ eventClass: String, _ payload: [String: Any]) {
        DispatchQueue.main.async {
            LaravelBridge.shared.send?("\(eventBase)\\\(eventClass)", payload)
        }
    }

    // ---------- Real implementations: banner ----------

    class LoadBanner: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            guard let slot = parameters["slot"] as? String else {
                return AdmobFunctions.notImplemented("LoadBanner: slot missing")
            }
            guard let unitId = parameters["unit_id"] as? String else {
                return AdmobFunctions.notImplemented("LoadBanner: unit_id missing")
            }

            DispatchQueue.main.async {
                let bannerView: BannerView
                if let existing = BannerRegistry.shared.get(slot: slot) {
                    bannerView = existing
                } else {
                    let width = AdmobFunctions.keyWindow()?.bounds.width ?? UIScreen.main.bounds.width
                    let size = currentOrientationAnchoredAdaptiveBanner(width: width)
                    bannerView = BannerView(adSize: size)
                    bannerView.adUnitID = unitId
                    bannerView.rootViewController = AdmobFunctions.rootViewController()

                    let delegate = BannerDelegate(slot: slot)
                    bannerView.delegate = delegate
                    objc_setAssociatedObject(bannerView, &BannerDelegate.associationKey, delegate, .OBJC_ASSOCIATION_RETAIN)

                    BannerRegistry.shared.put(slot: slot, ad: bannerView)
                }

                bannerView.load(Request())
            }

            return AdmobFunctions.success()
        }
    }

    class ShowBanner: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            guard let slot = parameters["slot"] as? String else {
                return AdmobFunctions.notImplemented("ShowBanner: slot missing")
            }
            let position = (parameters["position"] as? String) ?? "bottom"

            DispatchQueue.main.async {
                guard let bannerView = BannerRegistry.shared.get(slot: slot) else {
                    AdmobFunctions.dispatch("AdFailedToShow", ["slot": slot, "format": "banner", "error": "no_loaded_ad"])
                    return
                }
                guard let window = AdmobFunctions.keyWindow() else { return }

                if let existing = BannerRegistry.shared.removeContainer(slot: slot) {
                    existing.removeFromSuperview()
                }

                bannerView.removeFromSuperview()
                bannerView.translatesAutoresizingMaskIntoConstraints = false
                window.addSubview(bannerView)

                NSLayoutConstraint.activate([
                    bannerView.centerXAnchor.constraint(equalTo: window.centerXAnchor),
                    bannerView.widthAnchor.constraint(equalToConstant: bannerView.adSize.size.width),
                    bannerView.heightAnchor.constraint(equalToConstant: bannerView.adSize.size.height),
                    position == "top"
                        ? bannerView.topAnchor.constraint(equalTo: window.safeAreaLayoutGuide.topAnchor)
                        : bannerView.bottomAnchor.constraint(equalTo: window.safeAreaLayoutGuide.bottomAnchor),
                ])

                BannerRegistry.shared.putContainer(slot: slot, container: bannerView)
                AdmobFunctions.dispatch("AdShown", ["slot": slot, "format": "banner"])
            }

            return AdmobFunctions.success()
        }
    }

    class HideBanner: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            guard let slot = parameters["slot"] as? String else {
                return AdmobFunctions.notImplemented("HideBanner: slot missing")
            }

            DispatchQueue.main.async {
                if let container = BannerRegistry.shared.removeContainer(slot: slot) {
                    container.removeFromSuperview()
                }
            }

            return AdmobFunctions.success()
        }
    }

    // ---------- Always-real: platform identifier ----------

    class Platform: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            return AdmobFunctions.success(["platform": "ios"])
        }
    }

    // ---------- Stubs: land in later phases ----------

    class Start: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] { AdmobFunctions.notImplemented("Admob.Start") }
    }

    class LoadInterstitial: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] { AdmobFunctions.notImplemented("Admob.LoadInterstitial") }
    }

    class InterstitialReady: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] { AdmobFunctions.notImplemented("Admob.InterstitialReady") }
    }

    class ShowInterstitial: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] { AdmobFunctions.notImplemented("Admob.ShowInterstitial") }
    }

    class LoadRewarded: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] { AdmobFunctions.notImplemented("Admob.LoadRewarded") }
    }

    class RewardedReady: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] { AdmobFunctions.notImplemented("Admob.RewardedReady") }
    }

    class ShowRewarded: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] { AdmobFunctions.notImplemented("Admob.ShowRewarded") }
    }

    class LoadRewardedInterstitial: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] { AdmobFunctions.notImplemented("Admob.LoadRewardedInterstitial") }
    }

    class RewardedInterstitialReady: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] { AdmobFunctions.notImplemented("Admob.RewardedInterstitialReady") }
    }

    class ShowRewardedInterstitial: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] { AdmobFunctions.notImplemented("Admob.ShowRewardedInterstitial") }
    }

    class LoadAppOpen: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] { AdmobFunctions.notImplemented("Admob.LoadAppOpen") }
    }

    class UmpRequestInfo: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] { AdmobFunctions.notImplemented("Admob.UmpRequestInfo") }
    }

    class UmpShowForm: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] { AdmobFunctions.notImplemented("Admob.UmpShowForm") }
    }

    class UmpCanRequestAds: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] { AdmobFunctions.notImplemented("Admob.UmpCanRequestAds") }
    }

    class UmpStatus: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] { AdmobFunctions.notImplemented("Admob.UmpStatus") }
    }

    class UmpReset: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] { AdmobFunctions.notImplemented("Admob.UmpReset") }
    }

    class AttRequest: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] { AdmobFunctions.notImplemented("Admob.AttRequest") }
    }

    class AttStatus: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] { AdmobFunctions.notImplemented("Admob.AttStatus") }
    }
}

/// GADBannerView delegate that bridges native callbacks back to PHP events.
/// Each banner gets its own delegate instance retained via objc_setAssociatedObject
/// on the BannerView so it lives as long as the banner does.
private final class BannerDelegate: NSObject, BannerViewDelegate {
    fileprivate static var associationKey: UInt8 = 0

    let slot: String
    private let eventBase = "BlessedZulu\\NativePhpAdmob\\Events"

    init(slot: String) {
        self.slot = slot
    }

    private func dispatch(_ eventClass: String, _ payload: [String: Any]) {
        DispatchQueue.main.async {
            LaravelBridge.shared.send?("\(self.eventBase)\\\(eventClass)", payload)
        }
    }

    func bannerViewDidReceiveAd(_ bannerView: BannerView) {
        dispatch("AdLoaded", ["slot": slot, "format": "banner"])
    }

    func bannerView(_ bannerView: BannerView, didFailToReceiveAdWithError error: Error) {
        let nsError = error as NSError
        dispatch("AdFailedToLoad", [
            "slot": slot,
            "format": "banner",
            "errorCode": nsError.code,
            "errorMessage": nsError.localizedDescription,
        ])
    }

    func bannerViewDidRecordImpression(_ bannerView: BannerView) {
        dispatch("AdImpression", ["slot": slot, "format": "banner"])
    }

    func bannerViewDidRecordClick(_ bannerView: BannerView) {
        dispatch("AdClicked", ["slot": slot, "format": "banner"])
    }
}

import AppTrackingTransparency
import Foundation
import GoogleMobileAds
import UIKit
import UserMessagingPlatform

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

    static func dispatch(_ eventClass: String, _ payload: [String: Any]) {
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
            let offset = (parameters["offset"] as? NSNumber)?.doubleValue ?? 0
            let safeArea = (parameters["safe_area"] as? Bool) ?? true

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

                // safeArea=true anchors to the safe-area guide (clears the notch /
                // home indicator); false anchors to the raw window edge.
                let topAnchor = safeArea ? window.safeAreaLayoutGuide.topAnchor : window.topAnchor
                let bottomAnchor = safeArea ? window.safeAreaLayoutGuide.bottomAnchor : window.bottomAnchor

                NSLayoutConstraint.activate([
                    bannerView.centerXAnchor.constraint(equalTo: window.centerXAnchor),
                    bannerView.widthAnchor.constraint(equalToConstant: bannerView.adSize.size.width),
                    bannerView.heightAnchor.constraint(equalToConstant: bannerView.adSize.size.height),
                    position == "top"
                        ? bannerView.topAnchor.constraint(equalTo: topAnchor, constant: offset)
                        : bannerView.bottomAnchor.constraint(equalTo: bottomAnchor, constant: -offset),
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

    class Start: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            // SDK init happens in AdmobInit; Start applies the PHP-config-driven
            // RequestConfiguration - notably test device IDs, passed reliably as a
            // bridge param (config('admob.test_devices')) rather than via env.
            let testDevices = (parameters["test_devices"] as? [Any])?
                .compactMap { $0 as? String }
                .filter { !$0.isEmpty } ?? []
            if !testDevices.isEmpty {
                MobileAds.shared.requestConfiguration.testDeviceIdentifiers = testDevices
            }
            return AdmobFunctions.success(["started": true, "test_devices": testDevices.count])
        }
    }

    // Toggle the app-open auto-show (e.g. while a user holds an ad-free pass).
    class SetAppOpenSuppressed: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            let suppressed = parameters["suppressed"] as? Bool ?? false
            AppOpenLifecycle.autoShowSuppressed = suppressed
            return AdmobFunctions.success(["suppressed": suppressed])
        }
    }

    class LoadInterstitial: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            guard let slot = parameters["slot"] as? String else {
                return AdmobFunctions.notImplemented("LoadInterstitial: slot missing")
            }
            guard let unitId = parameters["unit_id"] as? String else {
                return AdmobFunctions.notImplemented("LoadInterstitial: unit_id missing")
            }

            InterstitialAd.load(with: unitId, request: Request()) { ad, error in
                if let error = error {
                    InterstitialRegistry.shared.remove(slot: slot)
                    let nsError = error as NSError
                    AdmobFunctions.dispatch("AdFailedToLoad", [
                        "slot": slot,
                        "format": "interstitial",
                        "errorCode": nsError.code,
                        "errorMessage": error.localizedDescription,
                    ])
                    return
                }
                guard let ad = ad else { return }
                let delegate = InterstitialDelegate(slot: slot)
                ad.fullScreenContentDelegate = delegate
                InterstitialRegistry.shared.put(slot: slot, ad: ad, delegate: delegate)
                AdmobFunctions.dispatch("AdLoaded", ["slot": slot, "format": "interstitial"])
            }

            return AdmobFunctions.success()
        }
    }

    class InterstitialReady: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            guard let slot = parameters["slot"] as? String else {
                return AdmobFunctions.notImplemented("InterstitialReady: slot missing")
            }
            return AdmobFunctions.success(["ready": InterstitialRegistry.shared.get(slot: slot) != nil])
        }
    }

    class ShowInterstitial: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            guard let slot = parameters["slot"] as? String else {
                return AdmobFunctions.notImplemented("ShowInterstitial: slot missing")
            }

            DispatchQueue.main.async {
                guard let ad = InterstitialRegistry.shared.get(slot: slot) else {
                    AdmobFunctions.dispatch("AdFailedToShow", [
                        "slot": slot, "format": "interstitial", "error": "no_loaded_ad",
                    ])
                    return
                }
                guard let root = AdmobFunctions.rootViewController() else {
                    AdmobFunctions.dispatch("AdFailedToShow", [
                        "slot": slot, "format": "interstitial", "error": "no_root_view_controller",
                    ])
                    return
                }
                ad.present(from: root)
            }

            return AdmobFunctions.success()
        }
    }

    class LoadRewarded: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            guard let slot = parameters["slot"] as? String else {
                return AdmobFunctions.notImplemented("LoadRewarded: slot missing")
            }
            guard let unitId = parameters["unit_id"] as? String else {
                return AdmobFunctions.notImplemented("LoadRewarded: unit_id missing")
            }

            RewardedAd.load(with: unitId, request: Request()) { ad, error in
                if let error = error {
                    RewardedRegistry.shared.remove(slot: slot)
                    let nsError = error as NSError
                    AdmobFunctions.dispatch("AdFailedToLoad", [
                        "slot": slot,
                        "format": "rewarded",
                        "errorCode": nsError.code,
                        "errorMessage": error.localizedDescription,
                    ])
                    return
                }
                guard let ad = ad else { return }
                let delegate = RewardedDelegate(slot: slot)
                ad.fullScreenContentDelegate = delegate
                RewardedRegistry.shared.put(slot: slot, ad: ad, delegate: delegate)
                AdmobFunctions.dispatch("AdLoaded", ["slot": slot, "format": "rewarded"])
            }

            return AdmobFunctions.success()
        }
    }

    class RewardedReady: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            guard let slot = parameters["slot"] as? String else {
                return AdmobFunctions.notImplemented("RewardedReady: slot missing")
            }
            return AdmobFunctions.success(["ready": RewardedRegistry.shared.get(slot: slot) != nil])
        }
    }

    class ShowRewarded: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            guard let slot = parameters["slot"] as? String else {
                return AdmobFunctions.notImplemented("ShowRewarded: slot missing")
            }

            DispatchQueue.main.async {
                guard let ad = RewardedRegistry.shared.get(slot: slot) else {
                    AdmobFunctions.dispatch("AdFailedToShow", [
                        "slot": slot, "format": "rewarded", "error": "no_loaded_ad",
                    ])
                    return
                }
                guard let root = AdmobFunctions.rootViewController() else {
                    AdmobFunctions.dispatch("AdFailedToShow", [
                        "slot": slot, "format": "rewarded", "error": "no_root_view_controller",
                    ])
                    return
                }
                ad.present(from: root) {
                    let reward = ad.adReward
                    AdmobFunctions.dispatch("UserEarnedReward", [
                        "slot": slot,
                        "format": "rewarded",
                        "type": reward.type,
                        "amount": Int(truncating: reward.amount),
                    ])
                }
            }

            return AdmobFunctions.success()
        }
    }

    class LoadRewardedInterstitial: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            guard let slot = parameters["slot"] as? String else {
                return AdmobFunctions.notImplemented("LoadRewardedInterstitial: slot missing")
            }
            guard let unitId = parameters["unit_id"] as? String else {
                return AdmobFunctions.notImplemented("LoadRewardedInterstitial: unit_id missing")
            }

            RewardedInterstitialAd.load(with: unitId, request: Request()) { ad, error in
                if let error = error {
                    RewardedInterstitialRegistry.shared.remove(slot: slot)
                    let nsError = error as NSError
                    AdmobFunctions.dispatch("AdFailedToLoad", [
                        "slot": slot,
                        "format": "rewarded_interstitial",
                        "errorCode": nsError.code,
                        "errorMessage": error.localizedDescription,
                    ])
                    return
                }
                guard let ad = ad else { return }
                let delegate = RewardedInterstitialDelegate(slot: slot)
                ad.fullScreenContentDelegate = delegate
                RewardedInterstitialRegistry.shared.put(slot: slot, ad: ad, delegate: delegate)
                AdmobFunctions.dispatch("AdLoaded", ["slot": slot, "format": "rewarded_interstitial"])
            }

            return AdmobFunctions.success()
        }
    }

    class RewardedInterstitialReady: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            guard let slot = parameters["slot"] as? String else {
                return AdmobFunctions.notImplemented("RewardedInterstitialReady: slot missing")
            }
            return AdmobFunctions.success(["ready": RewardedInterstitialRegistry.shared.get(slot: slot) != nil])
        }
    }

    class ShowRewardedInterstitial: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            guard let slot = parameters["slot"] as? String else {
                return AdmobFunctions.notImplemented("ShowRewardedInterstitial: slot missing")
            }

            DispatchQueue.main.async {
                guard let ad = RewardedInterstitialRegistry.shared.get(slot: slot) else {
                    AdmobFunctions.dispatch("AdFailedToShow", [
                        "slot": slot, "format": "rewarded_interstitial", "error": "no_loaded_ad",
                    ])
                    return
                }
                guard let root = AdmobFunctions.rootViewController() else {
                    AdmobFunctions.dispatch("AdFailedToShow", [
                        "slot": slot, "format": "rewarded_interstitial", "error": "no_root_view_controller",
                    ])
                    return
                }
                ad.present(from: root) {
                    let reward = ad.adReward
                    AdmobFunctions.dispatch("UserEarnedReward", [
                        "slot": slot,
                        "format": "rewarded_interstitial",
                        "type": reward.type,
                        "amount": Int(truncating: reward.amount),
                    ])
                }
            }

            return AdmobFunctions.success()
        }
    }

    class LoadAppOpen: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            guard let slot = parameters["slot"] as? String else {
                return AdmobFunctions.notImplemented("LoadAppOpen: slot missing")
            }
            guard let unitId = parameters["unit_id"] as? String else {
                return AdmobFunctions.notImplemented("LoadAppOpen: unit_id missing")
            }

            AppOpenAd.load(with: unitId, request: Request()) { ad, error in
                if let error = error {
                    AppOpenRegistry.shared.remove(slot: slot)
                    let nsError = error as NSError
                    AdmobFunctions.dispatch("AdFailedToLoad", [
                        "slot": slot,
                        "format": "app_open",
                        "errorCode": nsError.code,
                        "errorMessage": error.localizedDescription,
                    ])
                    return
                }
                guard let ad = ad else { return }
                let delegate = AppOpenDelegate(slot: slot)
                ad.fullScreenContentDelegate = delegate
                AppOpenRegistry.shared.put(slot: slot, ad: ad, delegate: delegate)
                AdmobFunctions.dispatch("AdLoaded", ["slot": slot, "format": "app_open"])
            }

            return AdmobFunctions.success()
        }
    }

    class AppOpenReady: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            guard let slot = parameters["slot"] as? String else {
                return AdmobFunctions.notImplemented("AppOpenReady: slot missing")
            }
            let hasAd = AppOpenRegistry.shared.get(slot: slot) != nil
            let fresh = AppOpenRegistry.shared.isFresh(slot: slot)
            return AdmobFunctions.success([
                "ready": hasAd && fresh,
                "fresh": fresh,
                "age_ms": AppOpenRegistry.shared.ageMs(slot: slot),
            ])
        }
    }

    class ShowAppOpen: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            guard let slot = parameters["slot"] as? String else {
                return AdmobFunctions.notImplemented("ShowAppOpen: slot missing")
            }

            DispatchQueue.main.async {
                guard let ad = AppOpenRegistry.shared.get(slot: slot) else {
                    AdmobFunctions.dispatch("AdFailedToShow", [
                        "slot": slot, "format": "app_open", "error": "no_loaded_ad",
                    ])
                    return
                }
                if !AppOpenRegistry.shared.isFresh(slot: slot) {
                    AppOpenRegistry.shared.remove(slot: slot)
                    AdmobFunctions.dispatch("AdFailedToShow", [
                        "slot": slot, "format": "app_open", "error": "stale",
                    ])
                    return
                }
                guard let root = AdmobFunctions.rootViewController() else {
                    AdmobFunctions.dispatch("AdFailedToShow", [
                        "slot": slot, "format": "app_open", "error": "no_root_view_controller",
                    ])
                    return
                }
                ad.present(from: root)
            }

            return AdmobFunctions.success()
        }
    }

    // ---------- Real implementations: UMP consent ----------

    class UmpRequestInfo: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            ConsentManager.info.requestConsentInfoUpdate(with: ConsentManager.requestParameters()) { error in
                if let error = error {
                    NSLog("UMP requestConsentInfoUpdate error: \(error.localizedDescription)")
                }
                AdmobFunctions.dispatch("ConsentChanged", ["status": ConsentManager.statusString()])
            }

            return AdmobFunctions.success()
        }
    }

    class UmpShowForm: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            DispatchQueue.main.async {
                guard let root = AdmobFunctions.rootViewController() else {
                    AdmobFunctions.dispatch("ConsentChanged", ["status": ConsentManager.statusString()])
                    return
                }
                if ConsentManager.isFormRequired() {
                    AdmobFunctions.dispatch("ConsentFormShown", [:])
                }
                ConsentForm.loadAndPresentIfRequired(from: root) { error in
                    if let error = error {
                        NSLog("UMP loadAndPresentIfRequired error: \(error.localizedDescription)")
                    }
                    let status = ConsentManager.statusString()
                    AdmobFunctions.dispatch("ConsentFormDismissed", ["status": status])
                    AdmobFunctions.dispatch("ConsentChanged", ["status": status])
                }
            }

            return AdmobFunctions.success()
        }
    }

    class UmpCanRequestAds: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            return AdmobFunctions.success(["can_request": ConsentManager.info.canRequestAds])
        }
    }

    class UmpStatus: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            return AdmobFunctions.success(["status": ConsentManager.statusString()])
        }
    }

    class UmpReset: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            ConsentManager.info.reset()

            return AdmobFunctions.success()
        }
    }

    // ---------- Real implementations: ATT (App Tracking Transparency) ----------

    class AttRequest: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            ATTrackingManager.requestTrackingAuthorization { status in
                let event = status == .authorized
                    ? "TrackingAuthorizationGranted"
                    : "TrackingAuthorizationDenied"
                AdmobFunctions.dispatch(event, [:])
            }

            return AdmobFunctions.success()
        }
    }

    class AttStatus: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            let status: String
            switch ATTrackingManager.trackingAuthorizationStatus {
            case .authorized: status = "authorized"
            case .denied: status = "denied"
            case .restricted: status = "restricted"
            case .notDetermined: status = "notDetermined"
            @unknown default: status = "notDetermined"
            }

            return AdmobFunctions.success(["status": status])
        }
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

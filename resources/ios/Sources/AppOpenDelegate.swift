import Foundation
import GoogleMobileAds

/// Bridges `FullScreenContentDelegate` callbacks for app-open ads to the
/// Laravel event bus. One instance per loaded ad, retained by
/// `AppOpenRegistry`. On dismissal or failure the registry slot is cleared.
final class AppOpenDelegate: NSObject, FullScreenContentDelegate {
    private let slot: String

    init(slot: String) {
        self.slot = slot
        super.init()
    }

    func adDidRecordImpression(_ ad: FullScreenPresentingAd) {
        AdmobFunctions.dispatch("AdImpression", ["slot": slot, "format": "app_open"])
    }

    func adDidRecordClick(_ ad: FullScreenPresentingAd) {
        AdmobFunctions.dispatch("AdClicked", ["slot": slot, "format": "app_open"])
    }

    func adWillPresentFullScreenContent(_ ad: FullScreenPresentingAd) {
        AdmobFunctions.dispatch("AdShown", ["slot": slot, "format": "app_open"])
    }

    func adDidDismissFullScreenContent(_ ad: FullScreenPresentingAd) {
        FullScreenAdState.markDismissed()
        AppOpenRegistry.shared.remove(slot: slot)
        AdmobFunctions.dispatch("AdDismissed", ["slot": slot, "format": "app_open"])
    }

    func ad(_ ad: FullScreenPresentingAd, didFailToPresentFullScreenContentWithError error: Error) {
        FullScreenAdState.markDismissed()
        AppOpenRegistry.shared.remove(slot: slot)
        let nsError = error as NSError
        AdmobFunctions.dispatch("AdFailedToShow", [
            "slot": slot,
            "format": "app_open",
            "errorCode": nsError.code,
            "errorMessage": error.localizedDescription,
        ])
    }
}

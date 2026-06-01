import Foundation
import GoogleMobileAds

/// Bridges Google's `FullScreenContentDelegate` callbacks to the Laravel
/// event bus.
///
/// One instance is created per loaded interstitial and retained by
/// `InterstitialRegistry`. On dismissal or failure it is removed from the
/// registry so the next load() / show() cycle starts clean.
final class InterstitialDelegate: NSObject, FullScreenContentDelegate {
    private let slot: String

    init(slot: String) {
        self.slot = slot
        super.init()
    }

    func adDidRecordImpression(_ ad: FullScreenPresentingAd) {
        AdmobFunctions.dispatch("AdImpression", ["slot": slot, "format": "interstitial"])
    }

    func adDidRecordClick(_ ad: FullScreenPresentingAd) {
        AdmobFunctions.dispatch("AdClicked", ["slot": slot, "format": "interstitial"])
    }

    func adWillPresentFullScreenContent(_ ad: FullScreenPresentingAd) {
        AdmobFunctions.dispatch("AdShown", ["slot": slot, "format": "interstitial"])
    }

    func adDidDismissFullScreenContent(_ ad: FullScreenPresentingAd) {
        FullScreenAdState.markDismissed()
        InterstitialRegistry.shared.remove(slot: slot)
        AdmobFunctions.dispatch("AdDismissed", ["slot": slot, "format": "interstitial"])
    }

    func ad(_ ad: FullScreenPresentingAd, didFailToPresentFullScreenContentWithError error: Error) {
        FullScreenAdState.markDismissed()
        InterstitialRegistry.shared.remove(slot: slot)
        let nsError = error as NSError
        AdmobFunctions.dispatch("AdFailedToShow", [
            "slot": slot,
            "format": "interstitial",
            "errorCode": nsError.code,
            "errorMessage": error.localizedDescription,
        ])
    }
}

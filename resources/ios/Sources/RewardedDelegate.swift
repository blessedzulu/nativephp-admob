import Foundation
import GoogleMobileAds

/// Bridges `FullScreenContentDelegate` callbacks for rewarded ads to the
/// Laravel event bus. One instance per loaded rewarded ad, retained by
/// `RewardedRegistry`. On dismissal or failure the registry slot is cleared.
///
/// Note: the `UserEarnedReward` event fires from the show closure, NOT from
/// any delegate method - that closure is wired in `ShowRewarded` directly.
final class RewardedDelegate: NSObject, FullScreenContentDelegate {
    private let slot: String

    init(slot: String) {
        self.slot = slot
        super.init()
    }

    func adDidRecordImpression(_ ad: FullScreenPresentingAd) {
        AdmobFunctions.dispatch("AdImpression", ["slot": slot, "format": "rewarded"])
    }

    func adDidRecordClick(_ ad: FullScreenPresentingAd) {
        AdmobFunctions.dispatch("AdClicked", ["slot": slot, "format": "rewarded"])
    }

    func adWillPresentFullScreenContent(_ ad: FullScreenPresentingAd) {
        AdmobFunctions.dispatch("AdShown", ["slot": slot, "format": "rewarded"])
    }

    func adDidDismissFullScreenContent(_ ad: FullScreenPresentingAd) {
        FullScreenAdState.markDismissed()
        RewardedRegistry.shared.remove(slot: slot)
        AdmobFunctions.dispatch("AdDismissed", ["slot": slot, "format": "rewarded"])
    }

    func ad(_ ad: FullScreenPresentingAd, didFailToPresentFullScreenContentWithError error: Error) {
        FullScreenAdState.markDismissed()
        RewardedRegistry.shared.remove(slot: slot)
        let nsError = error as NSError
        AdmobFunctions.dispatch("AdFailedToShow", [
            "slot": slot,
            "format": "rewarded",
            "errorCode": nsError.code,
            "errorMessage": error.localizedDescription,
        ])
    }
}

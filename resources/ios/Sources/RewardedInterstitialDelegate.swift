import Foundation
import GoogleMobileAds

/// Bridges `FullScreenContentDelegate` callbacks for rewarded interstitial ads
/// to the Laravel event bus. One instance per loaded ad, retained by
/// `RewardedInterstitialRegistry`. On dismissal or failure the registry slot
/// is cleared.
///
/// Note: `UserEarnedReward` fires from the show closure in
/// `ShowRewardedInterstitial`, not from any delegate method.
final class RewardedInterstitialDelegate: NSObject, FullScreenContentDelegate {
    private let slot: String

    init(slot: String) {
        self.slot = slot
        super.init()
    }

    func adDidRecordImpression(_ ad: FullScreenPresentingAd) {
        AdmobFunctions.dispatch("AdImpression", ["slot": slot, "format": "rewarded_interstitial"])
    }

    func adDidRecordClick(_ ad: FullScreenPresentingAd) {
        AdmobFunctions.dispatch("AdClicked", ["slot": slot, "format": "rewarded_interstitial"])
    }

    func adWillPresentFullScreenContent(_ ad: FullScreenPresentingAd) {
        AdmobFunctions.dispatch("AdShown", ["slot": slot, "format": "rewarded_interstitial"])
    }

    func adDidDismissFullScreenContent(_ ad: FullScreenPresentingAd) {
        FullScreenAdState.markDismissed()
        RewardedInterstitialRegistry.shared.remove(slot: slot)
        AdmobFunctions.dispatch("AdDismissed", ["slot": slot, "format": "rewarded_interstitial"])
    }

    func ad(_ ad: FullScreenPresentingAd, didFailToPresentFullScreenContentWithError error: Error) {
        FullScreenAdState.markDismissed()
        RewardedInterstitialRegistry.shared.remove(slot: slot)
        let nsError = error as NSError
        AdmobFunctions.dispatch("AdFailedToShow", [
            "slot": slot,
            "format": "rewarded_interstitial",
            "errorCode": nsError.code,
            "errorMessage": error.localizedDescription,
        ])
    }
}

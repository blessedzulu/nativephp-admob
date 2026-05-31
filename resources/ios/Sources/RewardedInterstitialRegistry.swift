import Foundation
import GoogleMobileAds

/// Slot-keyed registry for loaded `RewardedInterstitialAd` instances and their delegates.
///
/// Same delegate-retention story as `RewardedRegistry` / `InterstitialRegistry`.
/// One-shot: cleared in dismissal or failed-show callbacks.
final class RewardedInterstitialRegistry {
    static let shared = RewardedInterstitialRegistry()

    private var ads: [String: RewardedInterstitialAd] = [:]
    private var delegates: [String: RewardedInterstitialDelegate] = [:]

    private init() {}

    func put(slot: String, ad: RewardedInterstitialAd, delegate: RewardedInterstitialDelegate) {
        ads[slot] = ad
        delegates[slot] = delegate
    }

    func get(slot: String) -> RewardedInterstitialAd? {
        return ads[slot]
    }

    func remove(slot: String) {
        ads.removeValue(forKey: slot)
        delegates.removeValue(forKey: slot)
    }

    func clear() {
        ads.removeAll()
        delegates.removeAll()
    }
}

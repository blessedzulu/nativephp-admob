import Foundation
import GoogleMobileAds

/// Slot-keyed registry for loaded `RewardedAd` instances and their delegates.
///
/// Same as `InterstitialRegistry`: `FullScreenContentDelegate` is weakly held
/// by `RewardedAd`, so the registry strongly retains the delegate alongside the
/// ad until dismissal. Without that the delegate is deallocated before
/// `present(from:userDidEarnRewardHandler:)` fires its callbacks.
///
/// One-shot: the slot is cleared in dismissal or failed-show callbacks.
final class RewardedRegistry {
    static let shared = RewardedRegistry()

    private var ads: [String: RewardedAd] = [:]
    private var delegates: [String: RewardedDelegate] = [:]

    private init() {}

    func put(slot: String, ad: RewardedAd, delegate: RewardedDelegate) {
        ads[slot] = ad
        delegates[slot] = delegate
    }

    func get(slot: String) -> RewardedAd? {
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

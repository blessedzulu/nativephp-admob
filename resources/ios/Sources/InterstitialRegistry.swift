import Foundation
import GoogleMobileAds

/// Slot-keyed registry for loaded `InterstitialAd` instances and their delegates.
///
/// `FullScreenContentDelegate` is held weakly by `InterstitialAd`, so the
/// registry must keep a strong reference to the delegate alongside the ad
/// until the ad is dismissed - otherwise the delegate is deallocated before
/// `present(from:)` fires its callbacks.
///
/// Interstitials are one-shot: each loaded ad survives until shown (and
/// dismissed) or failed to show. The registry slot is cleared on dismissal
/// or failure.
///
/// Thread-safety: all mutations happen on the main thread (every caller is
/// expected to wrap operations in DispatchQueue.main.async).
final class InterstitialRegistry {
    static let shared = InterstitialRegistry()

    private var ads: [String: InterstitialAd] = [:]
    private var delegates: [String: InterstitialDelegate] = [:]

    private init() {}

    func put(slot: String, ad: InterstitialAd, delegate: InterstitialDelegate) {
        ads[slot] = ad
        delegates[slot] = delegate
    }

    func get(slot: String) -> InterstitialAd? {
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

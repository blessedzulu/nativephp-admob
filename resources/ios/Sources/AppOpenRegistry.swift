import Foundation
import GoogleMobileAds

/// Slot-keyed registry for loaded `AppOpenAd` instances, their delegates,
/// and their load timestamps for staleness checks.
///
/// Google strongly recommends discarding app-open ads older than 4 hours;
/// `isFresh(slot:)` enforces this.
///
/// The delegate map exists for the same retention reason as the
/// InterstitialRegistry - `FullScreenContentDelegate` is weakly held by the
/// SDK, so the registry must strongly retain it until dismissal.
final class AppOpenRegistry {
    static let shared = AppOpenRegistry()

    private var ads: [String: AppOpenAd] = [:]
    private var delegates: [String: AppOpenDelegate] = [:]
    private var loadTimes: [String: Date] = [:]

    private static let staleThreshold: TimeInterval = 4 * 60 * 60   // 4 hours

    private init() {}

    func put(slot: String, ad: AppOpenAd, delegate: AppOpenDelegate) {
        ads[slot] = ad
        delegates[slot] = delegate
        loadTimes[slot] = Date()
    }

    func get(slot: String) -> AppOpenAd? {
        return ads[slot]
    }

    func remove(slot: String) {
        ads.removeValue(forKey: slot)
        delegates.removeValue(forKey: slot)
        loadTimes.removeValue(forKey: slot)
    }

    func isFresh(slot: String) -> Bool {
        guard let t = loadTimes[slot] else { return false }
        return Date().timeIntervalSince(t) < AppOpenRegistry.staleThreshold
    }

    func ageMs(slot: String) -> Int {
        guard let t = loadTimes[slot] else { return -1 }
        return Int(Date().timeIntervalSince(t) * 1000)
    }

    func allSlots() -> [String] {
        return Array(ads.keys)
    }

    func clear() {
        ads.removeAll()
        delegates.removeAll()
        loadTimes.removeAll()
    }
}

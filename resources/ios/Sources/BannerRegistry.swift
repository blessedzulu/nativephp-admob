import Foundation
import UIKit
import GoogleMobileAds

/// Slot-keyed registry for banner ad views and their attachment containers.
///
/// iOS mirror of the Android `BannerRegistry`. Each loaded banner is kept
/// alive between load() and show() so that re-show is cheap and lifecycle
/// management (NotificationCenter background/foreground hooks) can operate
/// on the full set of active banners.
///
/// Thread-safety: all mutations happen on the main thread (every caller is
/// expected to wrap operations in DispatchQueue.main.async).
final class BannerRegistry {
    static let shared = BannerRegistry()

    private var ads: [String: BannerView] = [:]
    private var containers: [String: UIView] = [:]

    private init() {}

    func put(slot: String, ad: BannerView) {
        ads[slot] = ad
    }

    func get(slot: String) -> BannerView? {
        return ads[slot]
    }

    func putContainer(slot: String, container: UIView) {
        containers[slot] = container
    }

    func removeContainer(slot: String) -> UIView? {
        return containers.removeValue(forKey: slot)
    }

    func remove(slot: String) {
        if let container = containers.removeValue(forKey: slot) {
            container.removeFromSuperview()
        }
        ads.removeValue(forKey: slot)
    }

    func all() -> [BannerView] {
        return Array(ads.values)
    }

    func clear() {
        containers.values.forEach { $0.removeFromSuperview() }
        ads.removeAll()
        containers.removeAll()
    }
}

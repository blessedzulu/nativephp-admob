import Foundation
import UIKit
import GoogleMobileAds

/// Auto-show app-open ads when the app foregrounds via
/// `UIApplication.didBecomeActiveNotification`, after skipping the first
/// resume (cold-start splash). Honours the 4-hour staleness rule via
/// `AppOpenRegistry.shared.isFresh()`.
///
/// Stale ads are silently discarded; a fresh load() is NOT kicked off here -
/// that's the consumer's job.
///
/// Registered once at app boot from `AdmobInit`.
enum AppOpenLifecycle {
    private static var registered = false
    // iOS init runs after the app finishes launching; cold-start
    // didBecomeActive has already fired by then. Same reasoning as Android -
    // default to "consumed" so the first observed foreground triggers auto-show.
    private static var coldStartConsumed = true

    static func register() {
        guard !registered else { return }
        registered = true

        NotificationCenter.default.addObserver(
            forName: UIApplication.didBecomeActiveNotification,
            object: nil,
            queue: .main
        ) { _ in
            if !coldStartConsumed {
                coldStartConsumed = true
                return
            }
            for slot in AppOpenRegistry.shared.allSlots() {
                guard let ad = AppOpenRegistry.shared.get(slot: slot) else { continue }
                if !AppOpenRegistry.shared.isFresh(slot: slot) {
                    AppOpenRegistry.shared.remove(slot: slot)
                    continue
                }
                guard let root = AdmobFunctions.rootViewController() else { continue }
                ad.present(from: root)
            }
        }
    }
}

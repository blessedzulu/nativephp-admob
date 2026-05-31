import Foundation
import UIKit

/// Wires AdMob banner lifecycle into NativePHP's UIScene notifications.
///
/// Unlike Android, iOS GADBannerView does not require explicit
/// resume/pause calls - the system handles that. We still register the
/// observers so that any future per-banner cleanup (e.g. clearing on
/// background termination) has a known hook point.
///
/// Registered once at app boot from `AdmobInit.initialize()`.
enum BannerLifecycle {
    private static var registered = false

    static func register() {
        guard !registered else { return }
        registered = true

        NotificationCenter.default.addObserver(
            forName: UIApplication.didBecomeActiveNotification,
            object: nil,
            queue: .main
        ) { _ in
            // Banners auto-refresh; nothing required here. Future per-banner
            // hooks can iterate BannerRegistry.shared.all().
        }

        NotificationCenter.default.addObserver(
            forName: UIApplication.didEnterBackgroundNotification,
            object: nil,
            queue: .main
        ) { _ in
            // No-op for now; intentionally a hook point.
        }

        NotificationCenter.default.addObserver(
            forName: UIApplication.willTerminateNotification,
            object: nil,
            queue: .main
        ) { _ in
            BannerRegistry.shared.clear()
        }
    }
}

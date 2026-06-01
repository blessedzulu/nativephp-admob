import Foundation

/// Cross-format state for full-screen ad presentations.
///
/// Solves the problem where dismissing an interstitial/rewarded/rewarded-
/// interstitial/app-open ad triggers `UIApplication.didBecomeActive` (the
/// host scene regains focus after the SDK's full-screen view controller
/// tears down), which would otherwise cause `AppOpenLifecycle` to auto-show
/// the cached App Open ad immediately.
///
/// Every full-screen delegate's dismiss/fail callback marks
/// `lastDismissedAt`; AppOpenLifecycle's didBecomeActive observer suppresses
/// auto-show if within the grace window.
enum FullScreenAdState {
    static let dismissGraceSeconds: TimeInterval = 1.5

    static var lastDismissedAt: Date?

    static func markDismissed() {
        lastDismissedAt = Date()
    }

    static func recentlyDismissed() -> Bool {
        guard let last = lastDismissedAt else { return false }
        return Date().timeIntervalSince(last) < dismissGraceSeconds
    }
}

import Foundation
import GoogleMobileAds

/**
 * Boots the Google Mobile Ads SDK once at app startup. Registered as the
 * iOS init_function in nativephp.json so it runs before any bridge
 * function becomes available.
 *
 * Uses the modern v13+ async start() API. The Task wrapper means we don't
 * block the main thread waiting for adapter initialisation - the SDK
 * dispatches its own internal events as adapters come online.
 */
enum AdmobInit {
    static func initialize() {
        Task {
            _ = await MobileAds.shared.start()
        }

        if let raw = ProcessInfo.processInfo.environment["ADMOB_TEST_DEVICES"] {
            let ids = raw
                .split(separator: ",")
                .map { String($0).trimmingCharacters(in: .whitespaces) }
                .filter { !$0.isEmpty }

            if !ids.isEmpty {
                MobileAds.shared.requestConfiguration.testDeviceIdentifiers = ids
            }
        }
    }
}

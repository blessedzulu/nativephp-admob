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
 *
 * Test devices are managed in the AdMob console (Settings -> Test devices)
 * by raw advertising ID - one source of truth, no baked-in IDs to go stale.
 */
enum AdmobInit {
    static func initialize() {
        Task {
            _ = await MobileAds.shared.start()
        }

        BannerLifecycle.register()
        AppOpenLifecycle.register()
    }
}

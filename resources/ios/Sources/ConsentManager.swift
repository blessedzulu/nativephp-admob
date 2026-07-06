import Foundation
import UserMessagingPlatform

/**
 * Surrounds the UMP ConsentInformation singleton: debug-settings construction
 * (so the consent form can be forced during testing) and the consentStatus ->
 * PHP-string mapping aligned with ConsentChanged::STATUS_* on the Laravel side.
 *
 * NOTE: GoogleUserMessagingPlatform ~> 2.7 exposes the UMP-prefixed ObjC type
 * names to Swift (UMPConsentInformation / UMPConsentForm / UMPRequestParameters
 * / UMPDebugSettings / UMPDebugGeography / UMPConsentStatus), and the singleton
 * is `.sharedInstance` (not `.shared`). Verified against a real compile
 * (Xcode 26, iOS Simulator) 2026-07-06. The enum cases (.EEA / .notEEA /
 * .disabled, .notRequired / .required / .obtained) import de-prefixed and are
 * resolved from their property/parameter type, so they need no UMP prefix.
 */
enum ConsentManager {
    static var info: UMPConsentInformation { UMPConsentInformation.sharedInstance }

    static func requestParameters() -> UMPRequestParameters {
        let params = UMPRequestParameters()
        if let debug = debugSettings() {
            params.debugSettings = debug
        }

        return params
    }

    /**
     * Builds DebugSettings from env so the consent form's geography can be
     * forced during testing. ADMOB_UMP_DEBUG_GEOGRAPHY is one of
     * EEA / NOT_EEA / DISABLED. (Test devices are managed in the AdMob console,
     * not here; or simply use a VPN to a EEA region to exercise the real form.)
     */
    private static func debugSettings() -> UMPDebugSettings? {
        let env = ProcessInfo.processInfo.environment
        let geography = env["ADMOB_UMP_DEBUG_GEOGRAPHY"]?.trimmingCharacters(in: .whitespaces).uppercased()

        if geography == nil || geography == "" || geography == "DISABLED" {
            return nil
        }

        let settings = UMPDebugSettings()
        switch geography {
        case "EEA": settings.geography = .EEA
        case "NOT_EEA", "NON_EEA": settings.geography = .notEEA
        default: settings.geography = .disabled
        }

        return settings
    }

    /**
     * Maps ConsentInformation.consentStatus to the PHP string constants in
     * BlessedZulu\NativePhpAdmob\Events\ConsentChanged.
     */
    static func statusString() -> String {
        switch info.consentStatus {
        case .notRequired: return "not_required"
        case .required: return "required"
        case .obtained: return "obtained"
        default: return "unknown"
        }
    }

    static func isFormRequired() -> Bool {
        return info.consentStatus == .required
    }
}

import Foundation
import UserMessagingPlatform

/**
 * Surrounds the UMP ConsentInformation singleton: debug-settings construction
 * (so the consent form can be forced during testing) and the consentStatus ->
 * PHP-string mapping aligned with ConsentChanged::STATUS_* on the Laravel side.
 *
 * NOTE: GoogleUserMessagingPlatform ~> 2.7 exposes Swift-refined, de-prefixed
 * type names (ConsentInformation / ConsentForm / RequestParameters /
 * DebugSettings / DebugGeography / ConsentStatus), matching how this repo
 * already uses the de-prefixed Google Mobile Ads names (BannerView, Request,
 * AppOpenAd). If the pinned pod resolves only the UMP-prefixed ObjC names,
 * switch these to UMPConsentInformation.sharedInstance etc.
 */
enum ConsentManager {
    static var info: ConsentInformation { ConsentInformation.shared }

    static func requestParameters() -> RequestParameters {
        let params = RequestParameters()
        if let debug = debugSettings() {
            params.debugSettings = debug
        }

        return params
    }

    /**
     * Builds DebugSettings from env so the form can be triggered during testing.
     * ADMOB_UMP_DEBUG_GEOGRAPHY is one of EEA / NOT_EEA / DISABLED.
     * ADMOB_TEST_DEVICES is a comma-separated list of UMP hashed device IDs
     * (printed to the Xcode console on the first info-update on an unconfigured
     * device - NOT the MobileAds test-device ID).
     */
    private static func debugSettings() -> DebugSettings? {
        let env = ProcessInfo.processInfo.environment
        let geography = env["ADMOB_UMP_DEBUG_GEOGRAPHY"]?.trimmingCharacters(in: .whitespaces).uppercased()
        let devices = (env["ADMOB_TEST_DEVICES"] ?? "")
            .split(separator: ",")
            .map { $0.trimmingCharacters(in: .whitespaces) }
            .filter { !$0.isEmpty }

        if (geography == nil || geography == "" || geography == "DISABLED") && devices.isEmpty {
            return nil
        }

        let settings = DebugSettings()
        switch geography {
        case "EEA": settings.geography = .EEA
        case "NOT_EEA", "NON_EEA": settings.geography = .notEEA
        default: settings.geography = .disabled
        }
        if !devices.isEmpty {
            settings.testDeviceIdentifiers = devices
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

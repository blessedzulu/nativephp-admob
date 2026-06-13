package com.blessedzulu.nativephp.admob

import android.content.Context
import androidx.fragment.app.FragmentActivity
import com.google.android.ump.ConsentDebugSettings
import com.google.android.ump.ConsentInformation
import com.google.android.ump.ConsentRequestParameters
import com.google.android.ump.UserMessagingPlatform

/**
 * Holds the process-wide UMP ConsentInformation singleton and the helpers that
 * surround it: debug-settings construction (so the consent form can be forced
 * on a non-EEA test device) and the int-status -> PHP-string mapping that keeps
 * the native layer aligned with ConsentChanged::STATUS_* on the Laravel side.
 *
 * Mirrors the registry pattern used by the ad formats - a single object that
 * owns one piece of SDK state for the whole app.
 */
object ConsentManager {
    @Volatile
    private var cached: ConsentInformation? = null

    @Synchronized
    fun info(context: Context): ConsentInformation =
        cached ?: UserMessagingPlatform.getConsentInformation(context.applicationContext)
            .also { cached = it }

    fun requestParameters(context: Context): ConsentRequestParameters {
        val builder = ConsentRequestParameters.Builder()
        buildDebugSettings(context)?.let { builder.setConsentDebugSettings(it) }

        return builder.build()
    }

    /**
     * Builds ConsentDebugSettings from env so the consent form's geography can
     * be forced during testing. ADMOB_UMP_DEBUG_GEOGRAPHY is one of
     * EEA / NOT_EEA / DISABLED. (Test devices are managed in the AdMob console,
     * not here; the UMP debug geography honours a registered console test device
     * or simply use a VPN to a EEA region to exercise the real form.)
     */
    private fun buildDebugSettings(context: Context): ConsentDebugSettings? {
        val geography = System.getenv("ADMOB_UMP_DEBUG_GEOGRAPHY")?.trim()?.uppercase()

        if (geography.isNullOrBlank() || geography == "DISABLED") {
            return null
        }

        val builder = ConsentDebugSettings.Builder(context)
        when (geography) {
            "EEA" -> builder.setDebugGeography(ConsentDebugSettings.DebugGeography.DEBUG_GEOGRAPHY_EEA)
            "NOT_EEA", "NON_EEA" -> builder.setDebugGeography(ConsentDebugSettings.DebugGeography.DEBUG_GEOGRAPHY_NOT_EEA)
            else -> { /* leave default geography */ }
        }

        return builder.build()
    }

    /**
     * Maps ConsentInformation.consentStatus (int) to the PHP string constants in
     * BlessedZulu\NativePhpAdmob\Events\ConsentChanged.
     */
    fun statusString(info: ConsentInformation): String = when (info.consentStatus) {
        ConsentInformation.ConsentStatus.NOT_REQUIRED -> "not_required"
        ConsentInformation.ConsentStatus.REQUIRED -> "required"
        ConsentInformation.ConsentStatus.OBTAINED -> "obtained"
        else -> "unknown"
    }

    fun isFormRequired(info: ConsentInformation): Boolean =
        info.consentStatus == ConsentInformation.ConsentStatus.REQUIRED
}

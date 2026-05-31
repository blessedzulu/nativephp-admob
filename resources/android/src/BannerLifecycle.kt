package com.blessedzulu.nativephp.admob

import com.nativephp.mobile.lifecycle.NativePHPLifecycle

/**
 * Wires the AdMob banner lifecycle into NativePHP's activity callbacks.
 *
 * Google's AdView SDK requires resume() / pause() at the right moments to
 * keep impressions accurate and to avoid background battery drain.
 * onDestroy() releases the native resources.
 *
 * Registered once at app boot from AdmobInit.initialize().
 */
object BannerLifecycle {
    private var registered = false

    @JvmStatic
    @Synchronized
    fun register() {
        if (registered) return
        registered = true

        NativePHPLifecycle.on("onResume") {
            BannerRegistry.all().forEach { it.resume() }
        }

        NativePHPLifecycle.on("onPause") {
            BannerRegistry.all().forEach { it.pause() }
        }

        NativePHPLifecycle.on("onDestroy") {
            BannerRegistry.clear()
        }
    }
}

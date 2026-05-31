# Changelog

All notable changes to this project will be documented here. Format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/); versions follow [SemVer](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.5.0-alpha] - 2026-05-31

### Added

- **Real Interstitial ad implementation on Android.** `Admob::interstitial('slot')->load()` pre-loads an `InterstitialAd`, `isReady()` reports availability, `show()` presents the full-screen ad. Lifecycle events (`AdLoaded`, `AdFailedToLoad`, `AdShown`, `AdFailedToShow`, `AdDismissed`, `AdImpression`, `AdClicked`) dispatched via `FullScreenContentCallback`.
- **Real Interstitial ad implementation on iOS.** `InterstitialAd.load(with:request:)` + `FullScreenContentDelegate` wired the same way. **iOS is shipped untested on real hardware - please report issues at the GitHub issue tracker.**
- `InterstitialRegistry` (Android + iOS) slot-keyed map of loaded `InterstitialAd` instances. One-shot semantics: the slot is cleared on dismissal or failed-show, so the consumer must `load()` again before the next `show()`.
- `InterstitialDelegate` (iOS) - retains the `FullScreenContentDelegate` alongside the ad in the registry, since Google's SDK only holds the delegate weakly.

### Fixed

- **`AdShowFailed` dispatch string** renamed to canonical `AdFailedToShow` in `ShowBanner` failure path on both platforms. The PHP event class is `BlessedZulu\NativePhpAdmob\Events\AdFailedToShow`; the string mismatch would have silently swallowed the event for any production listener that did wire it. Phase 3 followup.

## [0.4.0-alpha] - 2026-05-31

### Changed

- **Pinned Android SDK versions to Kotlin-2.0-compatible majors.** `play-services-ads:24.0.0` (last version before the Kotlin 2.1 minimum bump in 24.1.0) and `user-messaging-platform:3.0.0`. NativePHP Mobile v3.3.5 ships a Kotlin 2.0 toolchain; binding to newer AdMob SDKs caused `Module was compiled with an incompatible version of Kotlin` failures at compile time.

### Added

- **`post_compile` substitution hook** (`nativephp:admob:substitute-placeholders`). NativePHP's compiler writes `${ADMOB_APP_ID}` verbatim into `AndroidManifest.xml` / `Info.plist` rather than resolving it from `getenv()`. The new console command runs after every `native:run` / `native:build` and rewrites known placeholders against the current env. Without it the AdMob SDK fails at boot with "Missing application ID".
- **Real Banner ad implementation on Android.** `Admob::banner('slot')->load()->show()` now renders a Google AdMob banner via `AdView`, attached to the activity's root view as an overlay. Position can be `'top'` or `'bottom'`. Lifecycle (pause/resume/destroy) is managed via NativePHP's lifecycle hooks.
- **Real Banner ad implementation on iOS.** `BannerView` (the iOS v13+ rename of `GADBannerView`) wired to the key window via Auto Layout. Following Google's canonical `developers.google.com/admob/ios/banner` reference. **iOS is shipped untested on real hardware — please report issues at the GitHub issue tracker.**
- `BannerRegistry` (Android + iOS) keyed by slot name, holding both the ad view and its attachment container.
- `BannerLifecycle` (Android + iOS) subscribing to `NativePHPLifecycle` events / `NotificationCenter` notifications so banners pause / resume / clean up correctly.
- Banner-side event dispatch for `AdLoaded`, `AdFailedToLoad`, `AdImpression`, `AdClicked`, `AdShown` so Livewire `#[OnNative]` listeners receive ad lifecycle callbacks.
- Adaptive banner sizing on both platforms using Google's `getCurrentOrientationAnchoredAdaptiveBannerAdSize` (Android) / `currentOrientationAnchoredAdaptiveBanner(width:)` (iOS).

### Changed

- **All 22 Kotlin bridge function classes now take `FragmentActivity` in their primary constructor** so they match the signature emitted by NativePHP's `AndroidPluginCompiler`. Phase 2's no-arg-constructor stubs would have failed to compile in any consumer's `native:run`; this release fixes that latent bug.

### Fixed

- **Banner load/show race.** PHP fires `Admob.LoadBanner` then `Admob.ShowBanner` synchronously on the bridge thread. `LoadBanner` posts the `AdView` creation to the UI thread and returns immediately, so `ShowBanner`'s registry lookup was hitting an empty slot. Moved the registry lookup into `runOnUiThread { … }` (Android) / `DispatchQueue.main.async { … }` (iOS) so it always lands after Load's queued work. Dispatches `AdShowFailed` (with `error=no_loaded_ad`) if the slot is still empty when the UI tick runs.

## [0.3.0-alpha] - 2026-05-31

### Added

- AdMob SDK declarations in `nativephp.json`:
  - Android: `com.google.android.gms:play-services-ads:25.3.0` and `com.google.android.ump:user-messaging-platform:4.0.0` Gradle deps.
  - iOS: `Google-Mobile-Ads-SDK ~> 13.4` and `GoogleUserMessagingPlatform ~> 2.7` CocoaPods.
- `ADMOB_APP_ID` declared as a required secret. The plugin writes it into `AndroidManifest.xml` (`<meta-data>` `com.google.android.gms.ads.APPLICATION_ID`) and `Info.plist` (`GADApplicationIdentifier`) automatically.
- 50 SKAdNetwork identifiers shipped in `Info.plist` `SKAdNetworkItems` as the iOS attribution starter set.
- `NSUserTrackingUsageDescription` shipped with a sensible default copy.
- Native init functions on both platforms (`AdmobInit.initialize`) that boot the Google Mobile Ads SDK at app start via NativePHP's `init_function` mechanism. Honours the `ADMOB_TEST_DEVICES` env var to register test device IDs.
- Stub Kotlin and Swift bridge function classes for every entry in the manifest's `bridge_functions`. Each returns a "not implemented in v0.3.x" error except `Admob.Platform`, which returns the real platform identifier.
- README configuration section documenting `ADMOB_APP_ID`, the SKAdNetwork refresh policy, and platform-specific manifest behaviour.

### Notes

- `native:plugin:validate` now passes against the manifest in a consumer app.
- A NativePHP build that registers the plugin will compile, link, and start on a real device with the AdMob SDK initialised at app boot.
- Calling any builder method outside `Admob::fake()` will dispatch an `AdFailedToLoad` / `AdFailedToShow` Laravel event with a "not implemented" message until Phase 3.

## [0.2.0-alpha] - 2026-05-31

### Added

- `Admob` manager with fluent builders for all five ad formats (banner, interstitial, rewarded, rewarded interstitial, app open).
- `Admob` facade with PHPDoc method hints + `Admob::fake()` static helper for tests.
- `Bridge` contract (`src/Contracts/Bridge.php`) with two implementations:
  - `NativeBridge` calls `nativephp_call()` at runtime, throws `BridgeUnavailableException` outside NativePHP.
  - `FakeBridge` records calls, accepts stubs, exposes `assertCalled` / `assertNotCalled` / `assertCalledTimes` / `simulateEvent`.
- `SlotResolver` translates configured slot names to AdMob ad unit IDs and forces Google's reserved test IDs when `test_mode` is on.
- `TestAdUnits` constants exposing Google's reserved test ad unit IDs for every format.
- 12 typed Laravel event classes covering the full ad + consent + tracking lifecycle, registered in `nativephp.json` for dual-dispatch to PHP + JS.
- `Ump` and `Att` sub-managers wrapping the consent and iOS tracking prompts.
- Internal `ConsentChanged` listener that keeps `Admob::canRequestAds()` cheap and synchronous.
- 22 bridge function declarations in `nativephp.json` for the native side to satisfy in Phase 3.
- 49 Pest tests covering Bridge mechanics, slot resolution, every builder, UMP, ATT, fake lifecycle, and an end-to-end rewarded ad flow.
- `branch-alias` for `dev-main` -> `0.1.x-dev` so local path-repo installs satisfy semver constraints.

### Notes

- No native iOS/Android code in this release. Calling any builder method outside `Admob::fake()` requires a NativePHP runtime; otherwise `BridgeUnavailableException` is thrown.
- Banner / Interstitial / Rewarded `show()` silently no-ops when consent has not been granted - by design.

## [0.1.0-alpha] - 2026-05-31

### Added

- Initial package scaffold (Phase 0): composer manifest, NativePHP manifest skeleton, service provider, facade, config schema, Pest + Testbench bootstrap, CI workflow, issue + PR templates, README, contributing guide, code of conduct, MIT license.

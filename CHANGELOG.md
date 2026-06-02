# Changelog

All notable changes to this project will be documented here. Format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/); versions follow [SemVer](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.12.0-alpha] - 2026-06-02

### Added

- **Banner offset.** The banner is a screen-edge native overlay, so it could sit on top of chrome like a native bottom-nav. You can now lift it off the edge: `config('admob.banner.offset.{top,bottom}')` (dp), or per call via `Admob::banner($slot)->show('bottom', 56)`, `<admob-banner offset="56">`, and `<x-admob::banner offset="56">`. Applied natively as a container margin (Android) / safe-area constraint constant (iOS, on top of the existing safe-area inset). The built-in test page gained an offset field.
- **The built-in test page now allows ads in `test_mode`** (forces `canRequestAds`), so it shows Google's demo ads without a real consent flow - the page is otherwise useless until consent resolves.

### Fixed

- **JS API calls are now serialized so concurrent calls can't race.** NativePHP correlates captured `fetch` bodies by URL, so several near-simultaneous POSTs to `/_admob/call` could drop bodies and return 422 (seen when multiple banners mount together). All callers - the `Admob` JS API, the `<x-admob::banner>` component, and the built-in test page - now chain requests through a single shared `window.__admobCallQueue`, sending them one at a time. Multi-banner screens (e.g. a sticky bottom banner + another placement) are race-free.

## [0.11.0-alpha] - 2026-06-02

### Added

- **Built-in test page.** A generic, self-contained HTML page served at `/_admob/test` (config `test_page`, default on outside production; route `test_route`) that exercises every ad format + the UMP/ATT flows and streams a live native-event log. No Livewire/Inertia/CSRF dependency - works in any NativePHP app. Set `NATIVEPHP_START_URL=/_admob/test` to boot into it.

### Fixed

- **`<x-admob::banner>` no longer reloads on every render.** It previously called `load()->show()` as a server-render side-effect, so every Livewire re-render re-loaded the banner (and on a page that re-renders in response to `AdLoaded`, it looped continuously). The component is now fully client-driven: it loads + shows **once** on init via `/_admob/call` and hides on navigation. (Requires `js_api`, default on.)

### Changed

- Converted stacked single-line (`//`) comment blocks to `/* */` blocks (notably throughout `config/admob.php`), matching the package's existing docblock style.

Makes the plugin usable from JavaScript (Inertia / Vue / React / vanilla), not just PHP.

### Added

- **JavaScript API.** A shipped ES module (`resources/js/admob.js`, published via `--tag=admob-js`) exposing `Admob.banner('slot').show('bottom')`, `Admob.interstitial('slot').load()/.isReady()/.show()`, `rewarded` / `rewardedInterstitial` / `appOpen`, and `Admob.ump.*` / `Admob.att.*` - plus an `Events` map and a TypeScript `.d.ts`. Every call POSTs to a thin same-origin endpoint that runs the PHP `Admob` facade, so slot resolution, the consent gate, frequency caps, and the kill-switch all apply server-side (no duplicated logic). Ad events still arrive via the runtime's `On()`.
- **`<admob-banner slot="..." position="...">` Web Component** - a framework-agnostic mirror of `<x-admob::banner>` (Vue / React / vanilla). Connect → load + show, disconnect → hide; the element lifecycle is the teardown signal.
- **`POST {js_api_prefix}/call`** route + `AdmobCallController` backing the JS API; toggle with `ADMOB_JS_API` (default true), prefix via `config('admob.js_api_prefix')` (default `_admob`). Requires the host to render `<meta name="csrf-token">`.

### Changed

- **`ADMOB_ENABLED` is now a real kill-switch.** Previously dead config; now when `false` it no-ops every ad `load()/show()/hide()` (and `isReady()` returns false) across all formats and both banner paths. Consent/ATT still run.
- **Banner auto-hide broadened.** `<x-admob::banner>` now listens on **both `window` and `document`** (Inertia dispatches its events on `document`, which the previous `window`-only listener missed) and cleans listeners up via an `AbortController` on teardown. Default `hide_on_events` is now `['livewire:navigating', 'inertia:before', 'pagehide']`.

## [0.9.0-alpha] - 2026-06-01

Polish pass before the Marketplace/v1.0 push.

### Added

- **`<x-admob::banner slot="..." position="bottom" />` Blade component.** Loads + shows a banner on render and tears the native overlay down on navigation. **No Livewire dependency** - teardown listens for configurable DOM events (`config('admob.banner.hide_on_events')`, default `['livewire:navigating']`) and calls `Admob.HideBanner` via NativePHP's JS bridge (`POST /_native/api/call`). Override the events for a different router, or set `[]` and call `->hide()` yourself.
- **Frequency caps.** Per-format and per-slot `min_interval_seconds` + `max_per_day` for the full-screen formats (banner exempt), configured under `config('admob.frequency')`. Persisted in the cache (survives relaunch, resets at local midnight); `test_mode` bypasses. A suppressed `show()` no-ops and dispatches the new **`AdShowThrottled`** event (`slot`, `format`, `reason`).
- **Debug tracing.** `ADMOB_DEBUG=true` wraps the bridge in a `LoggingBridge` that traces every call (method, params, response) at `debug` level.
- **Platform-aware test ad units.** `TestAdUnits::forPlatform()` resolves the App Open test ID per platform (Android `…/9257395921` vs iOS `…/5662855259`); previously hardcoded to Android, so the iOS App Open test ad would fail to load. All other formats remain universal. `FakeBridge::setPlatform()` added for tests.

### Changed

- **Bridge failures are no longer swallowed.** A new `AdBuilder` base routes every builder call through a `dispatch()` helper that logs a warning on a `success: false` response (no throw - a failed ad must not crash the app). All five builders now extend it.
- **Config + README made package-agnostic.** Removed the implication of an `ADMOB_{FORMAT}_{SLOT}` env-key convention - slots are resolved solely from `config('admob.slots.{format}.{name}')`, and any env names are the consumer's own choice. README gains "Where ad units are configured", a "Displaying each format" matrix, and frequency-cap / debug / failure-logging docs.

## [0.8.0-alpha] - 2026-06-01

Ships the two compliance surfaces (Phases 7 + 8): real UMP consent and real iOS App Tracking Transparency. With this release every previously-stubbed bridge function is real - only iOS device verification remains outstanding.

### Added — UMP consent

- **Real UMP (User Messaging Platform) consent implementation on Android.** The five `Admob::ump()` methods are now backed by Google's UMP SDK: `requestConsentInfo()` runs `requestConsentInfoUpdate`, `showFormIfRequired()` runs `loadAndShowConsentFormIfRequired`, and `canRequestAds()` / `status()` / `reset()` read and reset live consent state. This removes the need for the `Admob::setCanRequestAds(true)` test bypass - the real consent flow now drives the `show()`-time gate.
- **Real UMP implementation on iOS** using `GoogleUserMessagingPlatform`. Same surface. **iOS is shipped untested on real hardware - please report issues at the GitHub issue tracker.**
- `ConsentManager` (Android + iOS) - owns the process-wide `ConsentInformation` singleton, builds `ConsentDebugSettings` from `ADMOB_UMP_DEBUG_GEOGRAPHY` (`EEA` / `NOT_EEA` / `DISABLED`) + `ADMOB_TEST_DEVICES` (UMP hashed device IDs), and maps the SDK consent-status enum to the PHP `ConsentChanged::STATUS_*` strings.
- **New `ConsentFormDismissed` event** (carries the resolved `status`), fired when the consent form closes / is not required / errors. Registered in `nativephp.json`.
- `ConsentChanged` is now dispatched after every info-update and after every form dismissal, so the PHP-side consent cache stays accurate even in the common non-EEA "no form required" path.
- `ConsentFormShown` is dispatched only when a form is actually required (status `REQUIRED`), not on every `showFormIfRequired()` call.
- New `consent.ump_debug_geography` config key for discoverability (the native layer reads the env var directly).

### Added — iOS ATT

- **Real iOS App Tracking Transparency (ATT) implementation.** `Admob::att()->requestAuthorization()` now presents Apple's tracking prompt via `ATTrackingManager.requestTrackingAuthorization`, dispatching `TrackingAuthorizationGranted` or `TrackingAuthorizationDenied` on completion. `Admob::att()->status()` maps `ATTrackingManager.trackingAuthorizationStatus` to `authorized` / `denied` / `restricted` / `notDetermined`. **iOS is shipped untested on real hardware - please report issues at the GitHub issue tracker.**
- Android `AttRequest` / `AttStatus` are safe no-ops (the PHP `Att` layer already short-circuits to `unsupported` on non-iOS via the `Platform` bridge check, so these are never invoked there). `NSUserTrackingUsageDescription` is already declared in the manifest's `info_plist`; `AppTrackingTransparency` is a system framework, auto-linked on import - no CocoaPods entry needed.

### Changed

- **Consent-skip log level raised from `info` to `warning`** in every builder's `show()`. A silently-gated ad (consent not yet granted) is now visible at default production log levels, making the most common "ads never appear" misconfiguration diagnosable.

## [0.7.1-alpha] - 2026-06-01

### Fixed

- **App Open auto-show no longer fires after dismissing another full-screen ad.** When an interstitial / rewarded / rewarded-interstitial dismissed, MainActivity.onResume fired (because the SDK's full-screen activity tore down) - which the AppOpenLifecycle observer interpreted as a real foreground return and auto-showed the cached App Open ad. New `FullScreenAdState` singleton (Android + iOS) tracks last-dismissal time; AppOpenLifecycle now skips auto-show within a 1500ms grace window. Every full-screen FullScreenContentCallback / FullScreenContentDelegate marks the timestamp on dismissal + on failed-show.

## [0.7.0-alpha] - 2026-06-01

### Added

- **Real App Open ad implementation on Android.** `Admob::appOpen('slot')->load()` pre-loads. From there the native lifecycle observer (`AppOpenLifecycle`) automatically presents the cached ad on every app foreground after the first resume (cold-start skip). Honours Google's recommended 4-hour staleness check via `AppOpenRegistry.isFresh()`.
- **Real App Open ad implementation on iOS.** Same architecture - `AppOpenLifecycle` subscribes to `UIApplication.didBecomeActiveNotification`. **iOS is shipped untested on real hardware - please report issues at the GitHub issue tracker.**
- **`AppOpenAd::isReady()` and `AppOpenAd::show()`** added to the PHP builder for manual override when the auto-show flow doesn't fit. The recommended path is still auto-show via `load()`.
- `AppOpenRegistry` (Android + iOS) - slot-keyed map plus load-timestamp tracking. `isFresh(slot)` enforces the 4h threshold.
- `AppOpenLifecycle` (Android + iOS) - registers a resume/foreground observer once at app boot from `AdmobInit`. Skips the first resume; on subsequent resumes, shows the cached ad if fresh, discards if stale.
- 2 new bridge function declarations in `nativephp.json`: `Admob.AppOpenReady` and `Admob.ShowAppOpen`. The original v0.3 manifest only declared `Admob.LoadAppOpen` because that was all the PHP builder used at the time.

### Known limitations

- Stale ads are silently discarded; the plugin does NOT auto-load a replacement. Consumers should wire `#[OnNative(AdDismissed::class)]` to call `load()` again, or periodic-load if their app rarely backgrounds. A built-in auto-refresh helper is planned for Phase 9 polish.

## [0.6.0-alpha] - 2026-05-31

### Added

- **Real Rewarded ad implementation on Android.** `Admob::rewarded('slot')->load()->show()` presents Google's rewarded video flow. `OnUserEarnedRewardListener` fires the `UserEarnedReward` event with `slot`, `format`, `type`, `amount` on completion. Full lifecycle events (`AdLoaded`, `AdFailedToLoad`, `AdShown`, `AdFailedToShow`, `AdDismissed`, `AdImpression`, `AdClicked`) via `FullScreenContentCallback`.
- **Real Rewarded Interstitial ad implementation on Android.** Same API as rewarded but with auto-play-on-entry semantics + 5-second skip warning. Useful for between-level transitions.
- **Real Rewarded + Rewarded Interstitial on iOS.** Both wire `FullScreenContentDelegate` and pass the user-earned-reward closure to `present(from:userDidEarnRewardHandler:)`. **iOS is shipped untested on real hardware - please report issues at the GitHub issue tracker.**
- `RewardedRegistry` / `RewardedInterstitialRegistry` (Android + iOS) - slot-keyed maps. iOS variants also retain the delegate alongside the ad since Google's SDK only holds it weakly.

### Changed

- **BREAKING (pre-1.0):** `UserEarnedReward` event constructor signature now includes a `format` field as the second parameter: `__construct(string $slot, string $format, string $type, int $amount)`. Brings the event in line with all other Ad* events. Update any consumers that construct or destructure this event.

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

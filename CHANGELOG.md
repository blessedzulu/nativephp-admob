# Changelog

All notable changes to this project will be documented here. Format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/); versions follow [SemVer](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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
